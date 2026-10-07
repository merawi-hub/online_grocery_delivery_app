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
                'image_url' => $bp->product->image_url,
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
     * Store / Add a new product to catalog and branch inventory.
     */
    public function storeProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'category_id' => 'required|exists:categories,id',
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'pricing_type' => 'required|in:fixed_unit,weighted',
            'unit_of_measure' => 'required|string|max:20',
            'step_weight_grams' => 'nullable|integer',
            'image_url' => 'nullable|string',
            'is_perishable' => 'boolean',
            'price' => 'required|numeric|min:0.01',
            'discount_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|numeric|min:0',
            'is_in_stock' => 'boolean',
        ]);

        $branch = Branch::first();
        $branchId = $branch ? $branch->id : 1;

        $sku = !empty($validated['sku']) ? $validated['sku'] : 'PRD-' . strtoupper(Str::random(6));

        $product = DB::transaction(function () use ($validated, $sku, $branchId) {
            $prod = Product::create([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . strtolower(Str::random(4)),
                'sku' => $sku,
                'pricing_type' => $validated['pricing_type'],
                'unit_of_measure' => $validated['unit_of_measure'],
                'step_weight_grams' => $validated['step_weight_grams'] ?? null,
                'image_url' => $validated['image_url'] ?: 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=600&q=80',
                'is_perishable' => $validated['is_perishable'] ?? false,
                'is_active' => true,
            ]);

            BranchProduct::create([
                'branch_id' => $branchId,
                'product_id' => $prod->id,
                'price' => $validated['price'],
                'discount_price' => $validated['discount_price'] ?? null,
                'stock_quantity' => $validated['stock_quantity'],
                'is_in_stock' => $validated['is_in_stock'] ?? true,
            ]);

            return $prod;
        });

        return response()->json([
            'message' => 'Product successfully added to branch inventory',
            'product' => $product,
        ], 201);
    }

    /**
     * Edit full product details & branch price/stock.
     */
    public function updateProduct(Request $request, int $id): JsonResponse
    {
        $branchProduct = BranchProduct::with('product')->findOrFail($id);
        $product = $branchProduct->product;

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

        DB::transaction(function () use ($validated, $product, $branchProduct) {
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
                $branchProduct->update($bpFields);
            }
        });

        return response()->json([
            'message' => 'Product updated successfully',
            'item' => $branchProduct->fresh(['product.category']),
        ]);
    }

    /**
     * Delete / Deactivate product from branch inventory.
     */
    public function deleteProduct(int $id): JsonResponse
    {
        $branchProduct = BranchProduct::findOrFail($id);
        $branchProduct->delete();

        return response()->json([
            'message' => 'Product removed from branch inventory',
        ]);
    }

    /**
     * Update product price, stock quantity, and in-stock toggle.
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

        $branchProduct->update(array_filter($validated, fn($val) => !is_null($val)));

        return response()->json([
            'message' => 'Inventory updated successfully',
            'item' => $branchProduct,
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
}
