<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\DelivererController;
use App\Http\Controllers\Api\ManagerController;
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
    Route::get('/product-image/{filename}', [CatalogController::class, 'getProductImage']);
});

/*
|--------------------------------------------------------------------------
| General Operations Manager Endpoints (3rd Stakeholder: Admin)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::get('/overview', [AdminController::class, 'getOverview']);
    Route::get('/branches', [AdminController::class, 'getBranches']);
    
    // Stock Managers
    Route::get('/stock-managers', [AdminController::class, 'getStockManagers']);
    Route::post('/stock-managers', [AdminController::class, 'storeStockManager']);
    Route::patch('/stock-managers/{id}/branch', [AdminController::class, 'switchStockManagerBranch']);
    Route::patch('/stock-managers/{id}/approval', [AdminController::class, 'updateStockManagerApproval']);
    Route::delete('/stock-managers/{id}', [AdminController::class, 'deleteStockManager']);

    // Deliverers / Couriers
    Route::get('/deliverers', [AdminController::class, 'getDeliverers']);
    Route::post('/deliverers', [AdminController::class, 'storeDeliverer']);
    Route::patch('/deliverers/{id}/branch', [AdminController::class, 'assignDelivererBranch']);
    Route::patch('/deliverers/{id}/approval', [AdminController::class, 'updateDelivererApproval']);
    Route::delete('/deliverers/{id}', [AdminController::class, 'deleteDeliverer']);

    // Order Dispatch & Deliverer Assignment
    Route::post('/orders/{id}/assign-deliverer', [AdminController::class, 'assignDelivererToOrder']);

    // Products Management (Global Catalog)
    Route::get('/products', [AdminController::class, 'getProducts']);
    Route::post('/products', [AdminController::class, 'storeProduct']);
    Route::put('/products/{id}', [AdminController::class, 'updateProduct']);
    Route::delete('/products/{id}', [AdminController::class, 'deleteProduct']);

    // Categories Management
    Route::get('/categories', [AdminController::class, 'getCategories']);
    Route::post('/categories', [AdminController::class, 'storeCategory']);
    Route::put('/categories/{id}', [AdminController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [AdminController::class, 'deleteCategory']);
});

/*
|--------------------------------------------------------------------------
| Supermarket Stock Manager Endpoints (/admin123 backend)
|--------------------------------------------------------------------------
*/
Route::prefix('manager')->group(function () {
    Route::post('/register', [ManagerController::class, 'register']);
    Route::get('/stats', [ManagerController::class, 'getDashboardStats']);
    Route::get('/inventory', [ManagerController::class, 'getInventory']);
    Route::post('/products', [ManagerController::class, 'storeProduct']);
    Route::put('/products/{id}', [ManagerController::class, 'updateProduct']);
    Route::delete('/products/{id}', [ManagerController::class, 'deleteProduct']);
    Route::put('/inventory/{id}', [ManagerController::class, 'updateInventoryItem']);
    Route::get('/orders', [ManagerController::class, 'getOrders']);
    Route::patch('/orders/{id}/status', [ManagerController::class, 'updateOrderStatus']);
    Route::put('/branch', [ManagerController::class, 'updateBranchSettings']);
    Route::post('/upload-image', [ManagerController::class, 'uploadImage']);
    Route::post('/categories', [ManagerController::class, 'storeCategory']);
});

/*
|--------------------------------------------------------------------------
| Deliverer / Courier Fleet Endpoints (4th Stakeholder: Deliverer)
|--------------------------------------------------------------------------
*/
Route::prefix('driver')->group(function () {
    Route::get('/orders/available', [DelivererController::class, 'getAvailableOrders']);
    Route::get('/orders/active', [DelivererController::class, 'getActiveDelivery']);
    Route::post('/orders/{id}/pickup', [DelivererController::class, 'pickupOrder']);
    Route::post('/orders/{id}/deliver', [DelivererController::class, 'confirmDelivery']);
    Route::get('/history', [DelivererController::class, 'getHistory']);
    Route::post('/trips/{tripId}/location', [TrackingController::class, 'updateDriverLocation']);
});

/*
|--------------------------------------------------------------------------
| Orders & Tracking Endpoints (Customer & Deliverer Handover)
|--------------------------------------------------------------------------
*/
Route::prefix('orders')->group(function () {
    Route::get('/{id}/tracking', [TrackingController::class, 'trackOrder']);
    Route::post('/{id}/confirm-received', [OrderController::class, 'confirmReceived']);
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
    });
});
