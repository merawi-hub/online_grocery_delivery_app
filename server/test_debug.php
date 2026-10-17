<?php
// Test 1: Upload Image via /api/manager/upload-image
$tinyJpeg = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';

$ch = curl_init('http://127.0.0.1:8000/api/manager/upload-image');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['image' => $tinyJpeg]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "1. Upload Image Test: Code $code\n";
echo "Response: $res\n\n";

$uploadData = json_decode($res, true);
$imageUrl = $uploadData['url'] ?? $tinyJpeg;

// Test 2: Add Product "Baba" via /api/manager/products
$ch = curl_init('http://127.0.0.1:8000/api/manager/products');
$payload = [
    'name' => 'Baba',
    'category_id' => 15, // BB category
    'price' => 899.51,
    'stock_quantity' => 27,
    'unit_of_measure' => 'kg',
    'pricing_type' => 'weighted',
    'step_weight_grams' => 500,
    'sku' => '',
    'image_url' => $imageUrl,
    'is_in_stock' => true,
    'is_perishable' => false,
];

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
]);
$res2 = curl_exec($ch);
$code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "2. Add Product Test: Code $code2\n";
echo "Response: $res2\n\n";

// Test 3: Check Customer Catalog
$ch = curl_init('http://127.0.0.1:8000/api/catalog/products?category=bb');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$res3 = curl_exec($ch);
curl_close($ch);

$items = json_decode($res3, true);
echo "3. Catalog products in 'bb': " . count($items) . " item(s)\n";
foreach ($items as $item) {
    echo "- Name: {$item['name']}, Price: ETB {$item['price']}, Stock: {$item['stock_quantity']} {$item['unit_of_measure']}, Image: {$item['image_url']}\n";
}
