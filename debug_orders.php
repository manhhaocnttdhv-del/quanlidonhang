<?php
// Debug script để kiểm tra đơn hàng in_transit
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DEBUG ORDERS IN TRANSIT ===\n\n";

// Đếm tổng số đơn hàng in_transit
$totalInTransit = \App\Models\Order::where('status', 'in_transit')->count();
echo "Tổng số đơn hàng in_transit: {$totalInTransit}\n\n";

// Lấy một số đơn hàng mẫu
$orders = \App\Models\Order::where('status', 'in_transit')
    ->with('warehouse', 'toWarehouse')
    ->limit(10)
    ->get();

foreach ($orders as $order) {
    echo "Order: {$order->tracking_number}\n";
    echo "  - warehouse_id: " . ($order->warehouse_id ?? 'NULL') . "\n";
    echo "  - to_warehouse_id: " . ($order->to_warehouse_id ?? 'NULL') . "\n";
    echo "  - receiver_province: " . ($order->receiver_province ?? 'NULL') . "\n";
    echo "  - warehouse: " . ($order->warehouse ? $order->warehouse->name : 'NULL') . "\n";
    echo "  - toWarehouse: " . ($order->toWarehouse ? $order->toWarehouse->name : 'NULL') . "\n";
    echo "\n";
}

// Kiểm tra với một warehouse cụ thể (ví dụ: Nghệ An, ID = 1)
$warehouseId = 1;
$warehouse = \App\Models\Warehouse::find($warehouseId);
if ($warehouse) {
    echo "=== ORDERS FOR WAREHOUSE: {$warehouse->name} (ID: {$warehouseId}) ===\n\n";
    
    $ordersForWarehouse = \App\Models\Order::where('status', 'in_transit')
        ->where(function($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId)
              ->orWhere('to_warehouse_id', $warehouseId);
        })
        ->with('warehouse', 'toWarehouse')
        ->get();
    
    echo "Số đơn hàng cho kho này: " . $ordersForWarehouse->count() . "\n\n";
    
    foreach ($ordersForWarehouse as $order) {
        echo "Order: {$order->tracking_number}\n";
        echo "  - warehouse_id: {$order->warehouse_id} (xuất từ)\n";
        echo "  - to_warehouse_id: " . ($order->to_warehouse_id ?? 'NULL') . " (đến)\n";
        echo "\n";
    }
}

echo "\n=== END DEBUG ===\n";
