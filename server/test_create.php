<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "--- Testing /api/catalog/products?category=tare ---" . PHP_EOL;
$req = Illuminate\Http\Request::create('/api/catalog/products?category=tare', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
$res = $kernel->handle($req);
echo "Status: " . $res->getStatusCode() . PHP_EOL;
echo "Content: " . $res->getContent() . PHP_EOL;
