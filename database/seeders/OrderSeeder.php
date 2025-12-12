<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Warehouse;
use App\Models\Route;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        $drivers = Driver::all();
        $warehouses = Warehouse::all();
        $routes = Route::all();

        $statuses = ['pending', 'pickup_pending', 'picked_up', 'in_warehouse', 'in_transit', 'out_for_delivery', 'delivered', 'failed'];
        $serviceTypes = ['express', 'standard', 'economy'];

        for ($i = 1; $i <= 20; $i++) {
            $status = $statuses[array_rand($statuses)];
            $serviceType = $serviceTypes[array_rand($serviceTypes)];
            $customer = $customers->random();
            $pickupDriver = $drivers->random();
            $deliveryDriver = $drivers->random();
            $warehouse = $warehouses->random();
            $route = $routes->random();

            $weight = rand(1, 20) + (rand(0, 99) / 100);
            $codAmount = rand(0, 100) > 50 ? rand(100000, 5000000) : 0;

            // Generate tracking number
            $trackingNumber = 'VD' . date('Ymd') . strtoupper(Str::random(6));

            $order = Order::create([
                'tracking_number' => $trackingNumber,
                'customer_id' => $customer->id,
                'sender_name' => $customer->name,
                'sender_phone' => $customer->phone,
                'sender_address' => $customer->address,
                'sender_province' => $customer->province,
                'sender_district' => $customer->district,
                'sender_ward' => $customer->ward,
                'receiver_name' => 'Người nhận ' . $i,
                'receiver_phone' => '09' . rand(10000000, 99999999),
                'receiver_address' => rand(1, 999) . ' Đường ABC, Phường ' . rand(1, 20),
                'receiver_province' => ['Hồ Chí Minh', 'Hà Nội', 'Đà Nẵng'][array_rand(['Hồ Chí Minh', 'Hà Nội', 'Đà Nẵng'])],
                'receiver_district' => 'Quận ' . rand(1, 12),
                'receiver_ward' => 'Phường ' . rand(1, 20),
                'item_type' => ['Điện tử', 'Quần áo', 'Thực phẩm', 'Sách', 'Đồ chơi'][array_rand(['Điện tử', 'Quần áo', 'Thực phẩm', 'Sách', 'Đồ chơi'])],
                'weight' => $weight,
                'length' => rand(10, 50),
                'width' => rand(10, 50),
                'height' => rand(10, 50),
                'cod_amount' => $codAmount,
                'shipping_fee' => rand(20000, 100000),
                'service_type' => $serviceType,
                'status' => $status,
                'pickup_driver_id' => $status !== 'pending' ? $pickupDriver->id : null,
                'delivery_driver_id' => in_array($status, ['out_for_delivery', 'delivered', 'failed']) ? $deliveryDriver->id : null,
                'route_id' => in_array($status, ['in_transit', 'out_for_delivery', 'delivered']) ? $route->id : null,
                'warehouse_id' => in_array($status, ['in_warehouse', 'in_transit', 'out_for_delivery']) ? $warehouse->id : null,
                'pickup_scheduled_at' => $status !== 'pending' ? now()->subDays(rand(1, 5)) : null,
                'picked_up_at' => in_array($status, ['picked_up', 'in_warehouse', 'in_transit', 'out_for_delivery', 'delivered']) ? now()->subDays(rand(1, 4)) : null,
                'delivery_scheduled_at' => in_array($status, ['out_for_delivery', 'delivered', 'failed']) ? now()->subDays(rand(0, 2)) : null,
                'delivered_at' => $status === 'delivered' ? now()->subDays(rand(0, 1)) : null,
                'is_fragile' => rand(0, 100) > 70,
                'notes' => rand(0, 100) > 80 ? 'Ghi chú đặc biệt cho đơn hàng ' . $i : null,
                'created_by' => 1,
            ]);

            // Create order statuses
            $statusHistory = [
                ['status' => 'pending', 'notes' => 'Đơn hàng mới được tạo', 'created_at' => $order->created_at],
            ];

            if (in_array($status, ['pickup_pending', 'picking_up', 'picked_up', 'in_warehouse', 'in_transit', 'out_for_delivery', 'delivered', 'failed'])) {
                $statusHistory[] = [
                    'status' => 'pickup_pending',
                    'notes' => 'Đã phân công tài xế lấy hàng',
                    'driver_id' => $pickupDriver->id,
                    'created_at' => $order->pickup_scheduled_at,
                ];
            }

            if (in_array($status, ['picked_up', 'in_warehouse', 'in_transit', 'out_for_delivery', 'delivered', 'failed'])) {
                $statusHistory[] = [
                    'status' => 'picked_up',
                    'notes' => 'Đã lấy hàng thành công',
                    'driver_id' => $pickupDriver->id,
                    'created_at' => $order->picked_up_at,
                ];
            }

            if (in_array($status, ['in_warehouse', 'in_transit', 'out_for_delivery', 'delivered', 'failed'])) {
                $warehouseTime = $order->picked_up_at ? $order->picked_up_at->copy()->addHours(2) : now();
                $statusHistory[] = [
                    'status' => 'in_warehouse',
                    'notes' => 'Đã nhập kho',
                    'warehouse_id' => $warehouse->id,
                    'created_at' => $warehouseTime,
                ];
            }

            if (in_array($status, ['in_transit', 'out_for_delivery', 'delivered', 'failed'])) {
                $transitTime = $order->picked_up_at ? $order->picked_up_at->copy()->addDays(1) : now();
                $statusHistory[] = [
                    'status' => 'in_transit',
                    'notes' => 'Đang vận chuyển',
                    'warehouse_id' => $warehouse->id,
                    'created_at' => $transitTime,
                ];
            }

            if (in_array($status, ['out_for_delivery', 'delivered', 'failed'])) {
                $statusHistory[] = [
                    'status' => 'out_for_delivery',
                    'notes' => 'Đang giao hàng',
                    'driver_id' => $deliveryDriver->id,
                    'created_at' => $order->delivery_scheduled_at,
                ];
            }

            if ($status === 'delivered') {
                $statusHistory[] = [
                    'status' => 'delivered',
                    'notes' => 'Đã giao hàng thành công',
                    'driver_id' => $deliveryDriver->id,
                    'created_at' => $order->delivered_at,
                ];
            }

            if ($status === 'failed') {
                $failedTime = $order->delivery_scheduled_at ? $order->delivery_scheduled_at->copy()->addHours(2) : now();
                $statusHistory[] = [
                    'status' => 'failed',
                    'notes' => 'Giao hàng thất bại: Khách hàng không nhận',
                    'driver_id' => $deliveryDriver->id,
                    'created_at' => $failedTime,
                ];
            }

            foreach ($statusHistory as $statusData) {
                OrderStatus::create([
                    'order_id' => $order->id,
                    'status' => $statusData['status'],
                    'notes' => $statusData['notes'],
                    'warehouse_id' => $statusData['warehouse_id'] ?? null,
                    'driver_id' => $statusData['driver_id'] ?? null,
                    'updated_by' => 1,
                    'created_at' => $statusData['created_at'] ?? now(),
                    'updated_at' => $statusData['created_at'] ?? now(),
                ]);
            }
        }
    }
}
