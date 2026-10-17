<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Category;

$cats = Category::all(['id', 'name', 'slug']);
echo "Categories: " . json_encode($cats) . PHP_EOL;

$prods = Product::with('category', 'branchProducts')->get();
echo "Products: " . json_encode($prods) . PHP_EOL;
