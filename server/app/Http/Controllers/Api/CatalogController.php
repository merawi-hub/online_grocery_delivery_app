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
            ->with(['category', 'branchProducts']);

        if ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug)
                  ->orWhere('id', $categorySlug)
                  ->orWhere('name', $categorySlug);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->orderByDesc('id')->get()->map(function ($product) use ($branchId) {
            $branchStock = $product->branchProducts->firstWhere('branch_id', $branchId)
                ?? $product->branchProducts->first();

            $price = $branchStock ? floatval($branchStock->price) : 0.00;
            $stock = $branchStock ? floatval($branchStock->stock_quantity) : 0.00;
            $inStock = $branchStock ? (bool) $branchStock->is_in_stock : false;

            $imageUrl = $product->image_url;
            if ($imageUrl) {
                if (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://') || str_starts_with($imageUrl, 'data:')) {
                    // Full external or base64 data URL
                } else {
                    // Local stored image served directly via dedicated API streaming route
                    $cleanName = basename($imageUrl);
                    $imageUrl = url("/api/catalog/product-image/{$cleanName}");
                }
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'pricing_type' => $product->pricing_type, // 'fixed_unit' or 'weighted'
                'unit_of_measure' => $product->unit_of_measure,
                'step_weight_grams' => $product->step_weight_grams,
                'image_url' => $imageUrl ?: null,
                'is_perishable' => (bool) $product->is_perishable,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
                'price' => $price,
                'discount_price' => ($branchStock && $branchStock->discount_price) ? floatval($branchStock->discount_price) : null,
                'stock_quantity' => $stock,
                'is_in_stock' => $inStock && $stock > 0,
                'created_at' => $product->created_at ? $product->created_at->toISOString() : null,
                'is_recent' => $product->created_at ? $product->created_at->diffInDays(now()) < 7 : false,
            ];
        });

        return response()->json($products);
    }

    /**
     * Serve product image file reliably across all operating systems without symlink/junction issues.
     */
    public function getProductImage(string $filename): \Symfony\Component\HttpFoundation\Response
    {
        $cleanFilename = basename($filename);
        $candidates = [
            storage_path('app/public/products/' . $cleanFilename),
            storage_path('app/private/public/products/' . $cleanFilename),
            public_path('uploads/products/' . $cleanFilename),
            storage_path('app/public/' . $cleanFilename),
        ];

        foreach ($candidates as $filePath) {
            if (file_exists($filePath) && is_file($filePath)) {
                $mimeType = mime_content_type($filePath) ?: 'image/jpeg';
                return response()->file($filePath, [
                    'Content-Type' => $mimeType,
                    'Access-Control-Allow-Origin' => '*',
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
        }

        return response()->json(['message' => 'Image not found'], 404);
    }
}
