<?php
// Tiny 1x1 JPEG in base64
$tinyJpegBase64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';

$ch = curl_init('http://127.0.0.1:8000/api/manager/products');
$payload = [
    'name' => 'Uploaded Tariku Item',
    'category_id' => 14,
    'price' => 99.00,
    'stock_quantity' => 30,
    'unit_of_measure' => 'kg',
    'pricing_type' => 'weighted',
    'image_url' => $tinyJpegBase64,
    'is_in_stock' => true,
];

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Base64 Upload Creation Code: $httpCode\n";
echo "Response: $response\n";

// Now fetch from catalog
$ch = curl_init('http://127.0.0.1:8000/api/catalog/products?category=tare');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$res = curl_exec($ch);
curl_close($ch);
$products = json_decode($res, true);

echo "Products in tare:\n";
foreach ($products as $p) {
    echo "- ID: {$p['id']}, Name: {$p['name']}, Image: {$p['image_url']}\n";
    if (strpos($p['image_url'], '/api/catalog/product-image/') !== false) {
        $imgCh = curl_init($p['image_url']);
        curl_setopt($imgCh, CURLOPT_RETURNTRANSFER, true);
        $imgData = curl_exec($imgCh);
        $imgCode = curl_getinfo($imgCh, CURLINFO_HTTP_CODE);
        $imgType = curl_getinfo($imgCh, CURLINFO_CONTENT_TYPE);
        curl_close($imgCh);
        echo "   => Image fetch HTTP $imgCode, Content-Type: $imgType, Bytes: " . strlen($imgData) . "\n";
    }
}
