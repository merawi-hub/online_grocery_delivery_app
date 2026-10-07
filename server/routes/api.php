<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\TrackingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::prefix('catalog')->group(function () {
    Route::get('/supermarkets', [CatalogController::class, 'getSupermarkets']);
    Route::get('/branches', [CatalogController::class, 'getBranches']);
    Route::get('/categories', [CatalogController::class, 'getCategories']);
    Route::get('/products', [CatalogController::class, 'getProducts']);
});

/*
|--------------------------------------------------------------------------
| Protected Authenticated Routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Auth & Profile
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Granular Customer Addresses
    Route::prefix('addresses')->group(function () {
        Route::get('/', [AddressController::class, 'index']);
        Route::post('/', [AddressController::class, 'store']);
        Route::put('/{id}', [AddressController::class, 'update']);
        Route::delete('/{id}', [AddressController::class, 'destroy']);
        Route::patch('/{id}/default', [AddressController::class, 'setDefault']);
    });

    // Orders Lifecycle
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::get('/{id}/tracking', [TrackingController::class, 'trackOrder']);
    });

    // Driver Dispatch & Live Telemetry
    Route::prefix('driver')->group(function () {
        Route::post('/trips/{tripId}/location', [TrackingController::class, 'updateDriverLocation']);
    });
});
