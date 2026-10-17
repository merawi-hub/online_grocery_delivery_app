<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/storage/products/{filename}', function ($filename) {
    $path = storage_path('app/public/products/' . $filename);
    if (!file_exists($path)) {
        $path = storage_path('app/private/public/products/' . $filename);
    }
    if (!file_exists($path)) {
        $path = public_path('uploads/products/' . $filename);
    }
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, [
        'Access-Control-Allow-Origin' => '*',
        'Cache-Control' => 'public, max-age=86400',
    ]);
});

Route::get('/uploads/products/{filename}', function ($filename) {
    $path = public_path('uploads/products/' . $filename);
    if (!file_exists($path)) {
        $path = storage_path('app/public/products/' . $filename);
    }
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, [
        'Access-Control-Allow-Origin' => '*',
        'Cache-Control' => 'public, max-age=86400',
    ]);
});
