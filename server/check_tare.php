<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$prods = \App\Models\Product::where('category_id', 14)->get();
echo json_encode($prods, JSON_PRETTY_PRINT);
