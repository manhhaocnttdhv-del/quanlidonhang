<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\ShippingFeeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CodReconciliationController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\RouteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/tracking/{trackingNumber}', [TrackingController::class, 'getStatus']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Customers
    Route::apiResource('customers', CustomerController::class);

    // Orders - Tiếp nhận yêu cầu & Quản lý vận đơn
    Route::apiResource('orders', OrderController::class);
    Route::post('orders/{id}/update-status', [OrderController::class, 'updateStatus']);

    // Dispatch - Điều phối nhận
    Route::prefix('dispatch')->group(function () {
        Route::get('pending-pickups', [DispatchController::class, 'pendingPickups']);
        Route::post('assign-pickup-driver', [DispatchController::class, 'assignPickupDriver']);
        Route::post('update-pickup-status/{id}', [DispatchController::class, 'updatePickupStatus']);
        Route::get('available-drivers', [DispatchController::class, 'getAvailableDrivers']);
    });

    // Warehouse - Quản lý kho
    Route::apiResource('warehouses', WarehouseController::class);
    Route::post('warehouses/receive-order', [WarehouseController::class, 'receiveOrder']);
    Route::post('warehouses/release-order', [WarehouseController::class, 'releaseOrder']);
    Route::post('warehouses/ship-to-warehouse', [WarehouseController::class, 'shipToWarehouse']);
    Route::get('warehouses/{id}/inventory', [WarehouseController::class, 'getInventory']);
    Route::get('warehouses/{id}/transactions', [WarehouseController::class, 'getTransactions']);

    // Delivery - Giao hàng
    Route::prefix('delivery')->group(function () {
        Route::get('ready-for-delivery', [DeliveryController::class, 'readyForDelivery']);
        Route::post('assign-driver/{id}', [DeliveryController::class, 'assignDeliveryDriver']);
        Route::post('update-status/{id}', [DeliveryController::class, 'updateDeliveryStatus']);
        Route::get('statistics', [DeliveryController::class, 'getDriverStatistics']);
    });

    // Complaints - Sự cố - Khiếu nại
    Route::apiResource('complaints', ComplaintController::class);
    Route::post('complaints/{id}/resolve', [ComplaintController::class, 'resolve']);

    // Tracking - Theo dõi đơn hàng
    Route::post('tracking', [TrackingController::class, 'track']);

    // Shipping Fee - Tra cước
    Route::prefix('shipping-fees')->group(function () {
        Route::get('calculate', [ShippingFeeController::class, 'calculate']);
        Route::get('/', [ShippingFeeController::class, 'index']);
        Route::post('/', [ShippingFeeController::class, 'store']);
    });

    // Reports - Báo cáo
    Route::prefix('reports')->group(function () {
        Route::get('daily', [ReportController::class, 'daily']);
        Route::get('monthly', [ReportController::class, 'monthly']);
        Route::get('driver-performance', [ReportController::class, 'driverPerformance']);
        Route::get('warehouse', [ReportController::class, 'warehouse']);
        Route::get('warehouses-overview', [ReportController::class, 'warehousesOverview']);
        Route::get('revenue', [ReportController::class, 'revenue']);
    });

    // COD Reconciliation - Bảng kê
    Route::apiResource('cod-reconciliations', CodReconciliationController::class);
    Route::post('cod-reconciliations/{id}/update-payment', [CodReconciliationController::class, 'updatePayment']);

    // Drivers
    Route::apiResource('drivers', DriverController::class);

    // Routes
    Route::apiResource('routes', RouteController::class);
});
