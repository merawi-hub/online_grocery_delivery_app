<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supermarket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * Get active supermarkets with their branches.
     */
    public function getSupermarkets(): JsonResponse
    {
        $supermarkets = Supermarket::where('is_active', true)
            ->with(['branches' => function ($q) {
                $q->where('is_open', true);
            }])
            ->get();

        return response()->json($supermarkets);
    }

    /**
     * Get all active branches.
     */
    public function getBranches(): JsonResponse
    {
        $branches = Branch::where('is_open', true)
            ->with('supermarket')
            ->get();

        return response()->json($branches);
    }

    /**
     * Get categories list with active products count.
     */
    public function getCategories(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->withCount(['products' => function ($q) {
                $q->where('is_active', true);
            }])
            ->get();

        return response()->json($categories);
    }

    /**
     * Get products with branch pricing and inventory stock.
     */
    public function getProducts(Request $request): JsonResponse
    {
        $branchId = $request->query('branch_id', 1);
        $categorySlug = $request->query('category');
        $search = $request->query('search');

        $query = Product::where('is_active', true)
            ->with(['category', 'branchProducts' => function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            }]);

        if ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->get()->map(function ($product) {
            $branchStock = $product->branchProducts->first();
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'pricing_type' => $product->pricing_type, // 'fixed_unit' or 'weighted'
                'unit_of_measure' => $product->unit_of_measure,
                'step_weight_grams' => $product->step_weight_grams,
                'image_url' => $product->image_url,
                'is_perishable' => $product->is_perishable,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
                'price' => $branchStock ? $branchStock->price : 0.00,
                'discount_price' => $branchStock ? $branchStock->discount_price : null,
                'stock_quantity' => $branchStock ? $branchStock->stock_quantity : 0.00,
                'is_in_stock' => $branchStock ? $branchStock->is_in_stock : false,
            ];
        });

        return response()->json($products);
    }
}
