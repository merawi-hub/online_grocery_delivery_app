<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 1. Create a 1x1 transparent red png in base64
$testPngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

// 2. Make request to POST /api/manager/products
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$payload = [
    'name' => 'Real World Tariku Avocado',
    'category_id' => 'Tare',
    'price' => 65.00,
    'stock_quantity' => 40,
    'unit_of_measure' => 'piece',
    'pricing_type' => 'fixed_unit',
    'image_url' => $testPngBase64,
    'is_in_stock' => true,
];
$request = Illuminate\Http\Request::create('/api/manager/products', 'POST', [], [], [], [
    'HTTP_ACCEPT' => 'application/json',
    'CONTENT_TYPE' => 'application/json',
], json_encode($payload));

$response = $kernel->handle($request);
echo "Manager Product Creation Status: " . $response->getStatusCode() . PHP_EOL;
$createdData = json_decode($response->getContent(), true);
echo "Response message: " . ($createdData['message'] ?? 'none') . PHP_EOL;
$newProduct = $createdData['product'] ?? null;
echo "Created Product ID: " . ($newProduct['id'] ?? 'none') . ", Image: " . ($newProduct['image_url'] ?? 'none') . PHP_EOL;

// 3. Verify in Catalog API
$catalogReq = Illuminate\Http\Request::create('/api/catalog/products?category=tare', 'GET', [], [], [], [
    'HTTP_ACCEPT' => 'application/json',
]);
$catalogRes = $kernel->handle($catalogReq);
echo "Catalog API Status: " . $catalogRes->getStatusCode() . PHP_EOL;
$catalogProducts = json_decode($catalogRes->getContent(), true);
echo "Found " . count($catalogProducts) . " products in Tare category." . PHP_EOL;
foreach ($catalogProducts as $p) {
    echo " - Product: " . $p['name'] . ", Price: ETB " . $p['price'] . ", Image: " . $p['image_url'] . PHP_EOL;
}
