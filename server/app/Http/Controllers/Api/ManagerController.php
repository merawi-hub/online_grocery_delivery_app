<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ManagerController extends Controller
{
    /**
     * Store Manager Register / Onboard Branch
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone_number' => 'required|string|unique:users,phone_number',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'branch_name' => 'nullable|string|max:150',
        ]);

        $manager = User::create([
            'name' => $validated['name'],
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'store_manager',
            'phone_verified_at' => now(),
            'is_active' => true,
        ]);

        $token = $manager->createToken('manager-token')->plainTextToken;

        return response()->json([
            'message' => 'Store manager registered successfully',
            'user' => [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
                'phone_number' => $manager->phone_number,
                'role' => $manager->role,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Get branch operational metrics & KPIs for the manager dashboard.
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        $branch = Branch::first(); // Default to flagship branch
        $branchId = $branch ? $branch->id : 1;

        $orders = Order::where('branch_id', $branchId)->get();

        $totalRevenue = $orders->sum('estimated_total');
        $availableOrdersCount = $orders->whereIn('status', ['placed', 'picking', 'substitutions_review', 'packed', 'driver_assigned', 'out_for_delivery'])->count();
        $deliveredOrdersCount = $orders->where('status', 'delivered')->count();

        // Low stock count (items with stock < 20)
        $lowStockCount = BranchProduct::where('branch_id', $branchId)
            ->where('stock_quantity', '<=', 20)
            ->count();

        $recentOrders = Order::where('branch_id', $branchId)
            ->with(['customer', 'items.product', 'driverTrip.driver'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $categories = Category::where('is_active', true)->get(['id', 'name', 'slug']);

        return response()->json([
            'branch' => $branch,
            'categories' => $categories,
            'stats' => [
                'total_revenue' => round($totalRevenue, 2),
                'available_orders' => $availableOrdersCount,
                'delivered_orders' => $deliveredOrdersCount,
                'total_orders' => $orders->count(),
                'low_stock_alerts' => $lowStockCount,
            ],
            'recent_orders' => $recentOrders,
        ]);
    }

    /**
     * Get branch inventory with search & category filters.
     */
    public function getInventory(Request $request): JsonResponse
    {
        $branch = Branch::first();
        $branchId = $branch ? $branch->id : 1;
        $search = $request->query('search');

        $query = BranchProduct::where('branch_id', $branchId)
            ->with('product.category');

        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $items = $query->orderByDesc('id')->get()->map(function ($bp) {
            return [
                'id' => $bp->id,
                'product_id' => $bp->product_id,
                'name' => $bp->product->name,
                'sku' => $bp->product->sku,
                'category_id' => $bp->product->category_id,
                'category' => $bp->product->category ? $bp->product->category->name : 'General',
                'pricing_type' => $bp->product->pricing_type,
                'unit_of_measure' => $bp->product->unit_of_measure,
                'step_weight_grams' => $bp->product->step_weight_grams,
                'image_url' => $this->processImageUrl($bp->product->image_url),
                'is_perishable' => (bool) $bp->product->is_perishable,
                'price' => floatval($bp->price),
                'discount_price' => $bp->discount_price ? floatval($bp->discount_price) : null,
                'stock_quantity' => floatval($bp->stock_quantity),
                'is_in_stock' => (bool) $bp->is_in_stock,
            ];
        });

        return response()->json($items);
    }

    /**
     * Upload product image file directly to storage/products.
     * Supports both multipart/form-data file uploads and base64 image strings.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        // 1. Direct file upload
        if ($request->hasFile('image')) {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:10240',
            ]);

            $file = $request->file('image');
            $ext = $file->getClientOriginalExtension() ?: 'jpg';
            $targetDir = storage_path('app/public/products');
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $file->move($targetDir, $fileName);

            $relativeUrl = '/storage/products/' . $fileName;
            return response()->json([
                'url' => $relativeUrl,
                'full_url' => url('storage/products/' . $fileName),
                'filename' => $fileName,
                'message' => 'Image uploaded successfully',
            ]);
        }

        // 2. Base64 payload upload
        $imageParam = $request->input('image') ?? $request->input('image_url');
        if (!empty($imageParam) && is_string($imageParam)) {
            $savedUrl = $this->processImageUrl($imageParam);
            if ($savedUrl) {
                return response()->json([
                    'url' => $savedUrl,
                    'full_url' => url(ltrim($savedUrl, '/')),
                    'message' => 'Image processed and saved',
                ]);
            }
        }

        return response()->json(['message' => 'No valid image provided for upload.'], 422);
    }

    /**
     * Helper to process image URL, decode base64, or format public storage link.
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

        // If stored as relative /storage path
        if (str_starts_with($imageUrl, '/storage/')) {
            return $imageUrl;
        }

        return $imageUrl;
    }

    /**
     * Store / Add a new product to catalog and branch inventory.
     */
    public function storeProduct(Request $request): JsonResponse
    {
        // Normalize empty inputs to avoid rigid validation rejections
        if ($request->has('step_weight_grams') && ($request->step_weight_grams === '' || $request->step_weight_grams === null)) {
            $request->merge(['step_weight_grams' => null]);
        }
        if ($request->has('sku') && trim((string)$request->sku) === '') {
            $request->merge(['sku' => null]);
        }
        if ($request->has('discount_price') && ($request->discount_price === '' || $request->discount_price === null)) {
            $request->merge(['discount_price' => null]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'category_id' => 'required',
            'sku' => 'nullable|string|max:50',
            'pricing_type' => 'nullable|in:fixed_unit,weighted',
            'unit_of_measure' => 'nullable|string|max:50',
            'unit' => 'nullable|string|max:50',
            'step_weight_grams' => 'nullable|numeric',
            'image_url' => 'nullable|string',
            'is_perishable' => 'nullable|boolean',
            'price' => 'nullable|numeric|min:0.01',
            'base_price' => 'nullable|numeric|min:0.01',
            'discount_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|numeric|min:0',
            'is_in_stock' => 'nullable|boolean',
        ]);

        $branch = Branch::first();
        $branchId = $branch ? $branch->id : 1;

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

        // Generate non-colliding unique SKU if none provided, or ensure provided SKU is unique
        if (!empty($validated['sku'])) {
            $sku = trim((string)$validated['sku']);
            if (Product::where('sku', $sku)->exists()) {
                $baseSku = $sku;
                $counter = 1;
                while (Product::where('sku', $sku)->exists()) {
                    $sku = $baseSku . '-' . strtoupper(Str::random(3));
                }
            }
        } else {
            do {
                $sku = 'PRD-' . strtoupper(Str::random(6));
            } while (Product::where('sku', $sku)->exists());
        }

        $price = !empty($validated['price']) ? floatval($validated['price']) : (!empty($validated['base_price']) ? floatval($validated['base_price']) : 50.00);
        $imageUrl = $this->processImageUrl($validated['image_url'] ?? null);
        $pricingType = $validated['pricing_type'] ?? 'fixed_unit';
        $unitOfMeasure = $validated['unit_of_measure'] ?? $validated['unit'] ?? 'piece';
        $stockQuantity = isset($validated['stock_quantity']) ? floatval($validated['stock_quantity']) : 25;
        $isInStock = $validated['is_in_stock'] ?? true;
        $isPerishable = $validated['is_perishable'] ?? false;
        $stepWeight = ($pricingType === 'weighted') ? (!empty($validated['step_weight_grams']) ? intval($validated['step_weight_grams']) : 500) : null;

        $product = DB::transaction(function () use ($validated, $catId, $sku, $branchId, $imageUrl, $pricingType, $unitOfMeasure, $price, $stockQuantity, $isInStock, $isPerishable, $stepWeight) {
            $prod = Product::create([
                'category_id' => $catId,
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . strtolower(Str::random(4)),
                'sku' => $sku,
                'pricing_type' => $pricingType,
                'unit_of_measure' => $unitOfMeasure,
                'step_weight_grams' => $stepWeight,
                'image_url' => $imageUrl,
                'is_perishable' => $isPerishable,
                'is_active' => true,
            ]);

            // Link to ALL active supermarket branches so customers at any branch can buy it
            $allBranches = Branch::all();
            if ($allBranches->isEmpty()) {
                $allBranches = collect([(object)['id' => $branchId]]);
            }

            foreach ($allBranches as $b) {
                BranchProduct::updateOrCreate(
                    [
                        'branch_id' => $b->id,
                        'product_id' => $prod->id,
                    ],
                    [
                        'price' => $price,
                        'discount_price' => $validated['discount_price'] ?? null,
                        'stock_quantity' => $stockQuantity,
                        'is_in_stock' => $isInStock,
                    ]
                );
            }

            return $prod;
        });

        $productData = array_merge($product->load('category')->toArray(), [
            'price' => $price,
            'discount_price' => $validated['discount_price'] ?? null,
            'stock_quantity' => $stockQuantity,
            'is_in_stock' => $isInStock,
        ]);

        return response()->json([
            'message' => 'Product successfully added to catalog and made available to customers.',
            'product' => $productData,
        ], 201);
    }

    /**
     * Edit full product details & branch price/stock.
     * Works with either BranchProduct ID or Product ID to prevent mismatched edits.
     */
    public function updateProduct(Request $request, int $id): JsonResponse
    {
        $branchProduct = BranchProduct::find($id);
        if ($branchProduct) {
            $product = $branchProduct->product;
        } else {
            $product = Product::find($id);
            if ($product) {
                $branchProduct = BranchProduct::where('product_id', $product->id)->first();
            }
        }

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:150',
            'category_id' => 'sometimes|exists:categories,id',
            'pricing_type' => 'sometimes|in:fixed_unit,weighted',
            'unit_of_measure' => 'sometimes|string|max:20',
            'step_weight_grams' => 'nullable|integer',
            'image_url' => 'nullable|string',
            'is_perishable' => 'boolean',
            'price' => 'sometimes|numeric|min:0.01',
            'discount_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'sometimes|numeric|min:0',
            'is_in_stock' => 'boolean',
        ]);

        if (array_key_exists('image_url', $validated)) {
            $validated['image_url'] = $this->processImageUrl($validated['image_url']);
        }

        DB::transaction(function () use ($validated, $product) {
            $prodFields = array_intersect_key($validated, array_flip([
                'name', 'category_id', 'pricing_type', 'unit_of_measure', 'step_weight_grams', 'image_url', 'is_perishable'
            ]));
            if (!empty($prodFields)) {
                $product->update($prodFields);
            }

            $bpFields = array_intersect_key($validated, array_flip([
                'price', 'discount_price', 'stock_quantity', 'is_in_stock'
            ]));
            if (!empty($bpFields)) {
                // Update ALL branches for this product so changes reflect everywhere!
                BranchProduct::where('product_id', $product->id)->update($bpFields);
            }
        });

        $product->refresh()->load(['category', 'branchProducts']);
        $latestBp = $product->branchProducts->first();

        return response()->json([
            'message' => 'Product updated successfully across all branches',
            'item' => [
                'id' => $latestBp ? $latestBp->id : $product->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category_id' => $product->category_id,
                'category' => $product->category ? $product->category->name : 'General',
                'pricing_type' => $product->pricing_type,
                'unit_of_measure' => $product->unit_of_measure,
                'step_weight_grams' => $product->step_weight_grams,
                'image_url' => $this->processImageUrl($product->image_url),
                'is_perishable' => (bool) $product->is_perishable,
                'price' => $latestBp ? floatval($latestBp->price) : 0,
                'discount_price' => ($latestBp && $latestBp->discount_price) ? floatval($latestBp->discount_price) : null,
                'stock_quantity' => $latestBp ? floatval($latestBp->stock_quantity) : 0,
                'is_in_stock' => $latestBp ? (bool) $latestBp->is_in_stock : true,
            ],
            'product' => $product,
        ]);
    }

    /**
     * Delete product permanently from catalog and all branch inventories.
     */
    public function deleteProduct(int $id): JsonResponse
    {
        $branchProduct = BranchProduct::find($id);
        $productId = $branchProduct ? $branchProduct->product_id : $id;

        BranchProduct::where('product_id', $productId)->delete();
        Product::where('id', $productId)->delete();

        return response()->json([
            'message' => 'Product permanently removed from catalog and inventory',
        ]);
    }

    /**
     * Update product price, stock quantity, and in-stock toggle across all branches.
     */
    public function updateInventoryItem(Request $request, int $id): JsonResponse
    {
        $branchProduct = BranchProduct::findOrFail($id);

        $validated = $request->validate([
            'price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|numeric|min:0',
            'is_in_stock' => 'nullable|boolean',
        ]);

        $updateData = array_filter($validated, fn($val) => !is_null($val));
        if (!empty($updateData)) {
            // Update all branches for this product so customer view syncs immediately
            BranchProduct::where('product_id', $branchProduct->product_id)->update($updateData);
        }

        return response()->json([
            'message' => 'Inventory updated successfully',
            'item' => $branchProduct->fresh(),
        ]);
    }

    /**
     * Get all orders for the branch with filter by status or type (available vs delivered).
     */
    public function getOrders(Request $request): JsonResponse
    {
        $branch = Branch::first();
        $branchId = $branch ? $branch->id : 1;
        $status = $request->query('status');
        $type = $request->query('type'); // 'available' or 'delivered'

        $query = Order::where('branch_id', $branchId)
            ->with(['customer', 'items.product', 'driverTrip.driver']);

        if ($type === 'available') {
            $query->whereIn('status', ['placed', 'picking', 'substitutions_review', 'packed', 'driver_assigned', 'out_for_delivery']);
        } elseif ($type === 'delivered') {
            $query->where('status', 'delivered');
        } elseif ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $orders = $query->orderByDesc('created_at')->get();

        return response()->json($orders);
    }

    /**
     * Update an order's status.
     */
    public function updateOrderStatus(Request $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:placed,picking,substitutions_review,packed,driver_assigned,out_for_delivery,delivered,cancelled',
        ]);

        $order->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Order status updated to ' . $validated['status'],
            'order' => $order,
        ]);
    }

    /**
     * Update branch settings (operating hours, delivery radius, phone, open toggle).
     */
    public function updateBranchSettings(Request $request): JsonResponse
    {
        $branch = Branch::first();
        if (! $branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string',
            'contact_phone' => 'nullable|string',
            'delivery_radius_km' => 'nullable|numeric|min:1|max:50',
            'is_open' => 'nullable|boolean',
            'opening_time' => 'nullable|string',
            'closing_time' => 'nullable|string',
        ]);

        $branch->update(array_filter($validated, fn($val) => !is_null($val)));

        return response()->json([
            'message' => 'Branch settings saved successfully',
            'branch' => $branch,
        ]);
    }

    /**
     * Create a new category (Manager can create categories for their products).
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'icon_url' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        // Check if category already exists
        $existing = DB::table('categories')->where('name', $validated['name'])->first();
        if ($existing) {
            return response()->json([
                'message' => 'Category already exists.',
                'category' => $existing,
            ], 200);
        }

        $slug = Str::slug($validated['name']);
        $originalSlug = $slug;
        $counter = 1;
        while (DB::table('categories')->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $categoryId = DB::table('categories')->insertGetId([
            'name' => $validated['name'],
            'slug' => $slug,
            'icon_url' => $validated['icon_url'] ?? '🛒',
            'is_active' => $validated['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $category = DB::table('categories')->where('id', $categoryId)->first();

        return response()->json([
            'message' => 'Category created successfully.',
            'category' => $category,
        ], 201);
    }
}
