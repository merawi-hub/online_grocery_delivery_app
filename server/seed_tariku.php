<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Category;

// Delete temporary product 13
BranchProduct::where('product_id', 13)->delete();
Product::where('id', 13)->delete();

// Set up product 12 as "Tariku" under category "Tare"
$category = Category::where('name', 'Tare')->orWhere('slug', 'tare')->first();
if (!$category) {
    $category = Category::create([
        'name' => 'Tare',
        'slug' => 'tare',
        'icon_url' => '🛒',
        'is_active' => true,
    ]);
}

$tariku = Product::find(12);
if (!$tariku) {
    $tariku = new Product();
    $tariku->id = 12;
}

$tariku->name = 'Tariku';
$tariku->slug = 'tariku-grocery-special';
$tariku->sku = 'PRD-TARIKU01';
$tariku->category_id = $category->id;
$tariku->pricing_type = 'fixed_unit';
$tariku->unit_of_measure = 'pack';
$tariku->description = 'Tariku Premium Fresh Grocery Pack';
$tariku->image_url = 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=600&q=80';
$tariku->is_perishable = false;
$tariku->is_active = true;
$tariku->save();

// Ensure all branches have Tariku in stock
$branches = Branch::all();
foreach ($branches as $branch) {
    BranchProduct::updateOrCreate(
        [
            'branch_id' => $branch->id,
            'product_id' => $tariku->id,
        ],
        [
            'price' => 75.00,
            'discount_price' => 68.00,
            'stock_quantity' => 50,
            'is_in_stock' => true,
        ]
    );
}

echo "Tariku product configured successfully! Category: " . $category->name . " (ID: " . $category->id . ")" . PHP_EOL;
echo "Product ID: " . $tariku->id . ", Name: " . $tariku->name . ", In stock across " . $branches->count() . " branches." . PHP_EOL;
