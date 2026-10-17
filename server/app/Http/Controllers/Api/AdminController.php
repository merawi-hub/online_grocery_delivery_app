<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Category;
use App\Models\DriverTrip;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supermarket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * Get platform operational overview metrics for the 4th stakeholder (General Operations Manager).
     */
    public function getOverview(): JsonResponse
    {
        $totalStockManagers = User::where('role', 'store_manager')->count();
        $approvedStockManagers = User::where('role', 'store_manager')->where('approval_status', 'approved')->count();
        $pendingStockManagers = User::where('role', 'store_manager')->where('approval_status', 'pending')->count();

        $totalDeliverers = User::where('role', 'driver')->count();
        $approvedDeliverers = User::where('role', 'driver')->where('approval_status', 'approved')->count();
        $pendingDeliverers = User::where('role', 'driver')->where('approval_status', 'pending')->count();

        $totalCustomers = User::where('role', 'customer')->count();
        $totalSupermarkets = Supermarket::count();
        $totalBranches = Branch::count();
        $activeOrders = Order::whereIn('status', ['placed', 'picking', 'packed', 'driver_assigned', 'out_for_delivery'])->count();

        return response()->json([
            'metrics' => [
                'stock_managers' => [
                    'total' => $totalStockManagers,
                    'approved' => $approvedStockManagers,
                    'pending' => $pendingStockManagers,
                ],
                'deliverers' => [
                    'total' => $totalDeliverers,
                    'approved' => $approvedDeliverers,
                    'pending' => $pendingDeliverers,
                ],
                'customers' => [
                    'total' => $totalCustomers,
                ],
                'supermarkets' => [
                    'total' => $totalSupermarkets,
                    'branches' => $totalBranches,
                ],
                'orders' => [
                    'active' => $activeOrders,
                ],
            ],
        ]);
    }

    /**
     * List all Supermarkets & Branches with their assigned stock managers and deliverers.
     */
    public function getBranches(): JsonResponse
    {
        $branches = Branch::with([
            'supermarket',
            'managers:id,name,first_name,last_name,email,phone_number,branch_id,approval_status,is_active',
            'deliverers:id,name,first_name,last_name,email,phone_number,branch_id,vehicle_type,approval_status,is_active',
        ])
        ->withCount(['orders', 'branchProducts'])
        ->get()
        ->map(function ($branch) {
            $pendingOrdersCount = Order::where('branch_id', $branch->id)
                ->whereIn('status', ['placed', 'picking', 'packed', 'driver_assigned', 'out_for_delivery'])
                ->count();

            return [
                'id' => $branch->id,
                'supermarket_id' => $branch->supermarket_id,
                'supermarket_name' => $branch->supermarket ? $branch->supermarket->name : 'FreshMart',
                'supermarket_logo' => $branch->supermarket ? $branch->supermarket->logo_url : null,
                'name' => $branch->name,
                'address' => $branch->address,
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
                'contact_phone' => $branch->contact_phone,
                'is_open' => (bool) $branch->is_open,
                'delivery_radius_km' => $branch->delivery_radius_km,
                'total_products' => $branch->branch_products_count,
                'total_orders' => $branch->orders_count,
                'active_orders_count' => $pendingOrdersCount,
                'managers' => $branch->managers,
                'deliverers' => $branch->deliverers,
            ];
        });

        return response()->json([
            'branches' => $branches,
        ]);
    }

    /**
     * List all Stock Managers with branch details.
     */
    public function getStockManagers(): JsonResponse
    {
        $managers = User::where('role', 'store_manager')
            ->with('branch:id,name,address')
            ->orderByDesc('created_at')
            ->get([
                'id',
                'name',
                'first_name',
                'last_name',
                'phone_number',
                'email',
                'role',
                'branch_id',
                'approval_status',
                'is_active',
                'created_at',
            ]);

        return response()->json([
            'stock_managers' => $managers,
        ]);
    }

    /**
     * Create / Hire a new Stock Manager.
     */
    public function storeStockManager(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone_number' => 'required|string|unique:users,phone_number',
            'password' => 'required|string|min:6',
            'email' => 'nullable|email|unique:users,email',
            'branch_id' => 'nullable|exists:branches,id',
            'approval_status' => ['nullable', Rule::in(['approved', 'pending'])],
        ]);

        $fullName = trim($validated['first_name'] . ' ' . $validated['last_name']);

        $manager = User::create([
            'name' => $fullName,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'store_manager',
            'branch_id' => $validated['branch_id'] ?? null,
            'approval_status' => $validated['approval_status'] ?? 'approved',
            'phone_verified_at' => now(),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Stock Manager created successfully.',
            'stock_manager' => $manager->load('branch:id,name,address'),
        ], 201);
    }

    /**
     * Switch / Reassign a Stock Manager to a new branch, or swap two managers between branches.
     * Core requirement: "switch managers on the stock"
     */
    public function switchStockManagerBranch(Request $request, int $id): JsonResponse
    {
        $manager = User::where('role', 'store_manager')->findOrFail($id);

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'swap_with_manager_id' => 'nullable|exists:users,id',
        ]);

        // Swap mode: exchange branches between two managers
        if (!empty($validated['swap_with_manager_id'])) {
            $otherManager = User::where('role', 'store_manager')->findOrFail($validated['swap_with_manager_id']);

            $tempBranch = $manager->branch_id;
            $manager->branch_id = $otherManager->branch_id;
            $otherManager->branch_id = $tempBranch;

            $manager->save();
            $otherManager->save();

            return response()->json([
                'message' => "Successfully swapped branches between {$manager->name} and {$otherManager->name}.",
                'manager' => $manager->fresh(['branch:id,name,address']),
                'other_manager' => $otherManager->fresh(['branch:id,name,address']),
            ]);
        }

        // Direct branch switch mode
        $newBranchId = $validated['branch_id'] ?? null;
        $manager->branch_id = $newBranchId;
        $manager->save();

        $branchName = $manager->branch ? $manager->branch->name : 'Unassigned Pool';

        return response()->json([
            'message' => "Stock Manager '{$manager->name}' switched to {$branchName}.",
            'stock_manager' => $manager->fresh(['branch:id,name,address']),
        ]);
    }

    /**
     * Approve or update approval status of a Stock Manager.
     */
    public function updateStockManagerApproval(Request $request, int $id): JsonResponse
    {
        $manager = User::where('role', 'store_manager')->findOrFail($id);

        $request->validate([
            'approval_status' => ['required', Rule::in(['approved', 'pending', 'rejected'])],
        ]);

        $manager->approval_status = $request->input('approval_status');
        if ($manager->approval_status === 'approved') {
            $manager->is_active = true;
        }
        $manager->save();

        return response()->json([
            'message' => "Stock Manager status updated to {$manager->approval_status}.",
            'stock_manager' => $manager->load('branch:id,name,address'),
        ]);
    }

    /**
     * Remove an existed Stock Manager (delete or revoke).
     */
    public function deleteStockManager(int $id): JsonResponse
    {
        $manager = User::where('role', 'store_manager')->findOrFail($id);

        $name = $manager->name;
        $manager->tokens()->delete();
        $manager->delete();

        return response()->json([
            'message' => "Stock Manager '{$name}' has been successfully removed.",
        ]);
    }

    /**
     * List all Deliverers / Couriers with branch details.
     */
    public function getDeliverers(): JsonResponse
    {
        $deliverers = User::where('role', 'driver')
            ->with('branch:id,name,address')
            ->orderByDesc('created_at')
            ->get([
                'id',
                'name',
                'first_name',
                'last_name',
                'phone_number',
                'email',
                'role',
                'branch_id',
                'approval_status',
                'vehicle_type',
                'is_active',
                'created_at',
            ]);

        return response()->json([
            'deliverers' => $deliverers,
        ]);
    }

    /**
     * Create / Hire a new Deliverer / Courier.
     */
    public function storeDeliverer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone_number' => 'required|string|unique:users,phone_number',
            'password' => 'required|string|min:6',
            'email' => 'nullable|email|unique:users,email',
            'branch_id' => 'nullable|exists:branches,id',
            'vehicle_type' => 'nullable|string|max:50',
            'approval_status' => ['nullable', Rule::in(['approved', 'pending'])],
        ]);

        $fullName = trim($validated['first_name'] . ' ' . $validated['last_name']);

        $deliverer = User::create([
            'name' => $fullName,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'driver',
            'branch_id' => $validated['branch_id'] ?? null,
            'vehicle_type' => $validated['vehicle_type'] ?? 'Motorbike',
            'approval_status' => $validated['approval_status'] ?? 'approved',
            'phone_verified_at' => now(),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Deliverer created successfully.',
            'deliverer' => $deliverer->load('branch:id,name,address'),
        ], 201);
    }

    /**
     * Switch / Assign a Deliverer to a supermarket branch.
     * Core requirement: "assign deliverers(if needed)"
     */
    public function assignDelivererBranch(Request $request, int $id): JsonResponse
    {
        $deliverer = User::where('role', 'driver')->findOrFail($id);

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $deliverer->branch_id = $validated['branch_id'] ?? null;
        $deliverer->save();

        $branchName = $deliverer->branch ? $deliverer->branch->name : 'Unassigned Fleet Pool';

        return response()->json([
            'message' => "Deliverer '{$deliverer->name}' assigned to {$branchName}.",
            'deliverer' => $deliverer->fresh(['branch:id,name,address']),
        ]);
    }

    /**
     * Assign a Deliverer directly to an active order.
     * Core requirement: "assign deliverers(if needed)"
     */
    public function assignDelivererToOrder(Request $request, int $orderId): JsonResponse
    {
        $order = Order::findOrFail($orderId);

        $validated = $request->validate([
            'driver_id' => 'required|exists:users,id',
        ]);

        $driver = User::where('role', 'driver')->findOrFail($validated['driver_id']);

        DB::transaction(function () use ($order, $driver) {
            $trip = DriverTrip::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'driver_id' => $driver->id,
                    'status' => 'assigned',
                    'started_at' => now(),
                ]
            );

            // If order was packed or placed, update to driver_assigned
            if (in_array($order->status, ['placed', 'picking', 'packed'])) {
                $order->status = 'driver_assigned';
                $order->save();
            }
        });

        return response()->json([
            'message' => "Deliverer '{$driver->name}' assigned to order #{$order->order_number}.",
            'order' => $order->fresh(['branch', 'driverTrip.driver']),
        ]);
    }

    /**
     * Approve or update approval status of a Deliverer.
     */
    public function updateDelivererApproval(Request $request, int $id): JsonResponse
    {
        $deliverer = User::where('role', 'driver')->findOrFail($id);

        $request->validate([
            'approval_status' => ['required', Rule::in(['approved', 'pending', 'rejected'])],
        ]);

        $deliverer->approval_status = $request->input('approval_status');
        if ($deliverer->approval_status === 'approved') {
            $deliverer->is_active = true;
        }
        $deliverer->save();

        return response()->json([
            'message' => "Deliverer status updated to {$deliverer->approval_status}.",
            'deliverer' => $deliverer->load('branch:id,name,address'),
        ]);
    }

    /**
     * Remove an existed Deliverer.
     */
    public function deleteDeliverer(int $id): JsonResponse
    {
        $deliverer = User::where('role', 'driver')->findOrFail($id);

        $name = $deliverer->name;
        $deliverer->tokens()->delete();
        $deliverer->delete();

        return response()->json([
            'message' => "Deliverer '{$name}' has been successfully removed.",
        ]);
    }

    // ==================== Product Management ====================

    /**
     * List all products (global catalog).
     */
    /**
     * List all products (global catalog).
     */
    public function getProducts(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'branchProducts'])->orderByDesc('created_at');

        // Optional filters
        if ($request->has('category_id') && $request->input('category_id') !== 'all') {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $products = $query->get()->map(function ($p) {
            $bp = $p->branchProducts->first();
            return [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'image_url' => $p->image_url,
                'category_id' => $p->category_id,
                'category_name' => $p->category ? $p->category->name : 'Uncategorized',
                'base_price' => $bp ? floatval($bp->price) : 0.00,
                'unit' => $p->unit_of_measure ?: 'piece',
                'barcode' => $p->barcode ?: $p->sku,
                'is_active' => (bool) $p->is_active,
                'created_at' => $p->created_at ? $p->created_at->toISOString() : null,
                'updated_at' => $p->updated_at ? $p->updated_at->toISOString() : null,
            ];
        });

        return response()->json([
            'products' => $products,
        ]);
    }

    /**
     * Process image URL, decode base64, or format public storage link.
     */
    private function processImageUrl(?string $imageUrl): ?string
    {
        if (empty($imageUrl)) {
            return null;
        }

        // If base64 data URI was provided
        if (preg_match('/^data:image\/(\w+);base64,/', $imageUrl, $type)) {
            $data = substr($imageUrl, strpos($imageUrl, ',') + 1);
            $ext = strtolower($type[1]);
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $ext = 'jpg';
            }
            $decoded = base64_decode($data);
            if ($decoded !== false) {
                $storageDir = storage_path('app/public/products');
                if (!is_dir($storageDir)) {
                    mkdir($storageDir, 0755, true);
                }
                $fileName = 'prod_' . time() . '_' . Str::random(8) . '.' . $ext;
                file_put_contents($storageDir . '/' . $fileName, $decoded);
                return '/storage/products/' . $fileName;
            }
        }

        if (str_starts_with($imageUrl, '/storage/')) {
            return $imageUrl;
        }

        return $imageUrl;
    }

    /**
     * Create a new product in the global catalog and link to all active supermarket branches.
     */
    public function storeProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|string',
            'category_id' => 'required',
            'base_price' => 'nullable|numeric|min:0.01',
            'price' => 'nullable|numeric|min:0.01',
            'unit' => 'nullable|string|max:50',
            'unit_of_measure' => 'nullable|string|max:50',
            'pricing_type' => 'nullable|in:fixed_unit,weighted',
            'barcode' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        // Resilient category resolution (ID or Name or Slug)
        $catInput = $validated['category_id'] ?? null;
        if (is_numeric($catInput)) {
            $cat = Category::find($catInput);
            if (!$cat) {
                $cat = Category::first();
            }
            $catId = $cat ? $cat->id : 1;
        } else {
            $catName = trim((string)$catInput);
            if (!empty($catName)) {
                $cat = Category::where('name', $catName)->orWhere('slug', Str::slug($catName))->first();
                if (!$cat) {
                    $slug = Str::slug($catName);
                    $originalSlug = $slug;
                    $counter = 1;
                    while (Category::where('slug', $slug)->exists()) {
                        $slug = $originalSlug . '-' . $counter++;
                    }
                    $cat = Category::create([
                        'name' => $catName,
                        'slug' => $slug,
                        'icon_url' => '🛒',
                        'is_active' => true,
                    ]);
                }
                $catId = $cat->id;
            } else {
                $cat = Category::first();
                $catId = $cat ? $cat->id : 1;
            }
        }

        $imageUrl = $this->processImageUrl($validated['image_url'] ?? null);
        $slug = Str::slug($validated['name']) . '-' . strtolower(Str::random(4));
        do {
            $sku = 'PRD-' . strtoupper(Str::random(6));
        } while (Product::where('sku', $sku)->exists());
        $unitOfMeasure = $validated['unit_of_measure'] ?? $validated['unit'] ?? 'piece';
        $pricingType = $validated['pricing_type'] ?? 'fixed_unit';
        $price = !empty($validated['base_price']) ? floatval($validated['base_price']) : (!empty($validated['price']) ? floatval($validated['price']) : 50.00);
        $barcode = !empty($validated['barcode']) ? trim($validated['barcode']) : null;

        $product = DB::transaction(function () use ($validated, $catId, $imageUrl, $slug, $sku, $unitOfMeasure, $pricingType, $price, $barcode) {
            $prod = Product::create([
                'category_id' => $catId,
                'name' => $validated['name'],
                'slug' => $slug,
                'sku' => $sku,
                'barcode' => $barcode,
                'description' => $validated['description'] ?? null,
                'pricing_type' => $pricingType,
                'unit_of_measure' => $unitOfMeasure,
                'image_url' => $imageUrl,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Link to ALL active branches
            $branches = Branch::all();
            if ($branches->isEmpty()) {
                $branches = collect([(object)['id' => 1]]);
            }
            foreach ($branches as $b) {
                BranchProduct::updateOrCreate(
                    [
                        'branch_id' => $b->id,
                        'product_id' => $prod->id,
                    ],
                    [
                        'price' => $price,
                        'discount_price' => null,
                        'stock_quantity' => 50,
                        'is_in_stock' => $validated['is_active'] ?? true,
                    ]
                );
            }

            return $prod;
        });

        return response()->json([
            'message' => 'Product created successfully and published to all branches.',
            'product' => $product->load('category'),
        ], 201);
    }

    /**
     * Update an existing product in the global catalog.
     */
    public function updateProduct(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'base_price' => 'sometimes|required|numeric|min:0.01',
            'unit' => 'nullable|string|max:50',
            'unit_of_measure' => 'nullable|string|max:50',
            'pricing_type' => 'nullable|in:fixed_unit,weighted',
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')->ignore($id),
            ],
            'is_active' => 'nullable|boolean',
        ]);

        if (array_key_exists('image_url', $validated)) {
            $validated['image_url'] = $this->processImageUrl($validated['image_url']);
        }

        if (!empty($validated['unit']) && empty($validated['unit_of_measure'])) {
            $validated['unit_of_measure'] = $validated['unit'];
        }

        DB::transaction(function () use ($validated, $product) {
            $basePrice = $validated['base_price'] ?? null;
            $isActive = $validated['is_active'] ?? null;
            unset($validated['base_price'], $validated['unit']);

            $product->update($validated);

            $bpUpdates = [];
            if ($basePrice !== null) {
                $bpUpdates['price'] = $basePrice;
            }
            if ($isActive !== null) {
                $bpUpdates['is_in_stock'] = $isActive;
            }
            if (!empty($bpUpdates)) {
                BranchProduct::where('product_id', $product->id)->update($bpUpdates);
            }
        });

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product->fresh('category'),
        ]);
    }

    /**
     * Delete a product from the global catalog.
     */
    public function deleteProduct(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $name = $product->name;
        
        DB::transaction(function () use ($product) {
            BranchProduct::where('product_id', $product->id)->delete();
            $product->delete();
        });

        return response()->json([
            'message' => "Product '{$name}' has been successfully deleted.",
        ]);
    }

    // ==================== Category Management ====================

    /**
     * List all categories.
     */
    public function getCategories(): JsonResponse
    {
        $categories = DB::table('categories')
            ->leftJoin('products', 'categories.id', '=', 'products.category_id')
            ->select(
                'categories.id',
                'categories.name',
                'categories.slug',
                'categories.description',
                'categories.icon_url',
                'categories.is_active',
                'categories.created_at',
                'categories.updated_at',
                DB::raw('COUNT(products.id) as products_count')
            )
            ->groupBy(
                'categories.id',
                'categories.name',
                'categories.slug',
                'categories.description',
                'categories.icon_url',
                'categories.is_active',
                'categories.created_at',
                'categories.updated_at'
            )
            ->orderBy('categories.name')
            ->get()
            ->map(function ($cat) {
                $cat->icon = $cat->icon_url;
                return $cat;
            });

        return response()->json([
            'categories' => $categories,
        ]);
    }

    /**
     * Create a new category.
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'description' => 'nullable|string',
            'icon_url' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $icon = !empty($validated['icon_url']) ? $validated['icon_url'] : (!empty($validated['icon']) ? $validated['icon'] : '🛒');

        $slug = Str::slug($validated['name']);
        $originalSlug = $slug;
        $counter = 1;
        while (DB::table('categories')->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $categoryId = DB::table('categories')->insertGetId([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'icon_url' => $icon,
            'is_active' => $validated['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $category = DB::table('categories')->where('id', $categoryId)->first();
        if ($category) {
            $category->icon = $category->icon_url;
        }

        return response()->json([
            'message' => 'Category created successfully.',
            'category' => $category,
        ], 201);
    }

    /**
     * Update an existing category.
     */
    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $exists = DB::table('categories')->where('id', $id)->exists();
        if (!$exists) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')->ignore($id),
            ],
            'description' => 'nullable|string',
            'icon_url' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $updateData = [];
        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (array_key_exists('description', $validated)) {
            $updateData['description'] = $validated['description'];
        }
        if (isset($validated['icon_url']) || isset($validated['icon'])) {
            $updateData['icon_url'] = !empty($validated['icon_url']) ? $validated['icon_url'] : $validated['icon'];
        }
        if (isset($validated['is_active'])) {
            $updateData['is_active'] = $validated['is_active'];
        }
        $updateData['updated_at'] = now();

        DB::table('categories')->where('id', $id)->update($updateData);

        $category = DB::table('categories')->where('id', $id)->first();
        if ($category) {
            $category->icon = $category->icon_url;
        }

        return response()->json([
            'message' => 'Category updated successfully.',
            'category' => $category,
        ]);
    }

    /**
     * Delete a category.
     */
    public function deleteCategory(int $id): JsonResponse
    {
        $category = DB::table('categories')->where('id', $id)->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        // Check if category has products
        $hasProducts = DB::table('products')->where('category_id', $id)->exists();

        if ($hasProducts) {
            return response()->json([
                'message' => 'Cannot delete category. It contains one or more products.',
            ], 409);
        }

        DB::table('categories')->where('id', $id)->delete();

        return response()->json([
            'message' => "Category '{$category->name}' has been successfully deleted.",
        ]);
    }
}
