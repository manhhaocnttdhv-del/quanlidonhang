<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\ShippingFeeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CodReconciliationController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\RouteController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Auth\LoginController;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin Routes
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{id}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{id}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{id}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    
    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::delete('/orders/{id}', [OrderController::class, 'destroy'])->name('orders.destroy');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{id}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{id}', [OrderController::class, 'update'])->name('orders.update');
    Route::post('/orders/{id}/update-status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    
    // Dispatch
    Route::get('/dispatch', [DispatchController::class, 'index'])->name('dispatch.index');
    Route::post('/dispatch/assign-pickup-driver', [DispatchController::class, 'assignPickupDriver'])->name('dispatch.assign-pickup-driver');
    Route::post('/dispatch/auto-assign-pickup-driver', [DispatchController::class, 'autoAssignPickupDriver'])->name('dispatch.auto-assign-pickup-driver');
    Route::post('/dispatch/update-pickup-status/{id}', [DispatchController::class, 'updatePickupStatus'])->name('dispatch.update-pickup-status');
    
    // Warehouses
    Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::get('/warehouses/create', [WarehouseController::class, 'create'])->name('warehouses.create');
    Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
    Route::get('/warehouses/{id}', [WarehouseController::class, 'show'])->name('warehouses.show');
    Route::get('/warehouses/{id}/edit', [WarehouseController::class, 'edit'])->name('warehouses.edit');
    Route::put('/warehouses/{id}', [WarehouseController::class, 'update'])->name('warehouses.update');
    Route::delete('/warehouses/{id}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');
    Route::post('/warehouses/receive-order', [WarehouseController::class, 'receiveOrder'])->name('warehouses.receive-order');
    Route::post('/warehouses/bulk-receive-order', [WarehouseController::class, 'bulkReceiveOrder'])->name('warehouses.bulk-receive-order');
    Route::post('/warehouses/release-order', [WarehouseController::class, 'releaseOrder'])->name('warehouses.release-order');
    Route::post('/warehouses/ship-to-warehouse', [WarehouseController::class, 'shipToWarehouse'])->name('warehouses.ship-to-warehouse');
    Route::post('/warehouses/bulk-release-order', [WarehouseController::class, 'bulkReleaseOrder'])->name('warehouses.bulk-release-order');
    
    // Users (chỉ super admin)
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->except(['destroy']);
    Route::delete('/users/{id}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');
    
    // Delivery
    Route::get('/delivery', [DeliveryController::class, 'index'])->name('delivery.index');
    Route::get('/delivery/delivered', [DeliveryController::class, 'deliveredOrders'])->name('delivery.delivered');
    Route::post('/delivery/assign-driver/{id}', [DeliveryController::class, 'assignDeliveryDriver'])->name('delivery.assign-driver');
    Route::post('/delivery/bulk-assign-driver', [DeliveryController::class, 'bulkAssignDeliveryDriver'])->name('delivery.bulk-assign-driver');
    Route::post('/delivery/update-status/{id}', [DeliveryController::class, 'updateDeliveryStatus'])->name('delivery.update-status');
    
    // Tracking
    Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');
    Route::post('/tracking', [TrackingController::class, 'track'])->name('tracking.track');
    
    // Shipping Fees
    Route::get('/shipping-fees', [ShippingFeeController::class, 'index'])->name('shipping-fees.index');
    Route::post('/shipping-fees', [ShippingFeeController::class, 'store'])->name('shipping-fees.store');
    Route::post('/shipping-fees/calculate', [ShippingFeeController::class, 'calculate'])->name('shipping-fees.calculate');
    
    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/warehouses-overview', [ReportController::class, 'warehousesOverview'])->name('reports.warehouses-overview');
    
    // COD Reconciliations
    Route::get('/cod-reconciliations', [CodReconciliationController::class, 'index'])->name('cod-reconciliations.index');
    Route::post('/cod-reconciliations', [CodReconciliationController::class, 'store'])->name('cod-reconciliations.store');
    Route::get('/cod-reconciliations/{id}', [CodReconciliationController::class, 'show'])->name('cod-reconciliations.show');
    Route::post('/cod-reconciliations/{id}/update-payment', [CodReconciliationController::class, 'updatePayment'])->name('cod-reconciliations.update-payment');
    
    // Drivers
    Route::get('/drivers', [DriverController::class, 'index'])->name('drivers.index');
    Route::post('/drivers', [DriverController::class, 'store'])->name('drivers.store');
    Route::get('/drivers/{id}', [DriverController::class, 'show'])->name('drivers.show');
    Route::get('/drivers/{id}/edit', [DriverController::class, 'edit'])->name('drivers.edit');
    Route::put('/drivers/{id}', [DriverController::class, 'update'])->name('drivers.update');
    Route::delete('/drivers/{id}', [DriverController::class, 'destroy'])->name('drivers.destroy');
    
    // Routes
    Route::get('/routes', [RouteController::class, 'index'])->name('routes.index');
    Route::post('/routes', [RouteController::class, 'store'])->name('routes.store');
    Route::get('/routes/{id}', [RouteController::class, 'show'])->name('routes.show');
    
    // Address - Địa chỉ
    Route::get('/api/wards', function (\Illuminate\Http\Request $request) {
        $provinceCode = $request->get('province_code');
        
        if (!$provinceCode) {
            return response()->json(['error' => 'province_code is required'], 400);
        }
        
        $wards = \App\Models\Ward::where('province_code', $provinceCode)
            ->orderBy('ward_name')
            ->get(['ward_code', 'ward_name', 'province_code']);
        
        return response()->json($wards);
    })->name('api.wards');
    
    Route::get('/api/provinces', function () {
        $provinces = \App\Models\Province::orderBy('name')->get(['province_code', 'name']);
        return response()->json($provinces);
    })->name('api.provinces');
    
    // Get warehouses by province
    Route::get('/api/warehouses', function (\Illuminate\Http\Request $request) {
        $province = $request->get('province');
        
        if (!$province) {
            return response()->json(['error' => 'province is required'], 400);
        }
        
        // Normalize province name để match với database
        // "Thành phố Hồ Chí Minh" -> "Hồ Chí Minh"
        // "Thành phố Hà Nội" -> "Hà Nội"
        // "Tỉnh Đà Nẵng" -> "Đà Nẵng"
        $normalizedProvince = $province;
        $normalizedProvince = preg_replace('/^Thành phố\s+/', '', $normalizedProvince);
        $normalizedProvince = preg_replace('/^Tỉnh\s+/', '', $normalizedProvince);
        $normalizedProvince = trim($normalizedProvince);
        
        // Tìm kho với tên tỉnh đã normalize hoặc tên gốc
        $warehouses = \App\Models\Warehouse::where('is_active', true)
            ->where(function($query) use ($province, $normalizedProvince) {
                $query->where('province', $province)
                      ->orWhere('province', $normalizedProvince)
                      ->orWhere('province', 'like', '%' . $normalizedProvince . '%');
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'address', 'province']);
        
        return response()->json($warehouses);
    })->name('api.warehouses');
});
