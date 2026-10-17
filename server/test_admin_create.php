<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "--- Testing /api/admin/products with barcode: '' ---" . PHP_EOL;
$req = Illuminate\Http\Request::create('/api/admin/products', 'POST', [
    'name' => 'Admin Tariku Test',
    'category_id' => 14,
    'base_price' => 50,
    'unit' => 'piece',
    'barcode' => '',
    'is_active' => true,
], [], [], ['HTTP_ACCEPT' => 'application/json']);
$res = $kernel->handle($req);
echo "Status: " . $res->getStatusCode() . PHP_EOL;
echo "Content: " . $res->getContent() . PHP_EOL;
