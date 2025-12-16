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
        $drivers = Driver::all();
        
        // Lấy tất cả các kho (chỉ có Hà Nội và Sài Gòn)
        $warehouses = Warehouse::all();
        $haNoiWarehouse = $warehouses->where('province', 'Hà Nội')->first();
        $hcmWarehouse = $warehouses->where('province', 'Thành phố Hồ Chí Minh')->first() 
            ?? $warehouses->where('province', 'Hồ Chí Minh')->first();
        
        // Kho mặc định (fallback)
        $defaultWarehouse = $haNoiWarehouse ?? $warehouses->first();
        
        $routes = Route::all();

        $statuses = ['pending', 'pickup_pending', 'picked_up', 'in_warehouse', 'in_transit', 'out_for_delivery', 'delivered', 'failed'];
        $serviceTypes = ['express', 'standard', 'economy'];
        
        // Danh sách tỉnh nhận - chỉ có 2 kho: Hà Nội và Sài Gòn
        $priorityProvinces = ['Hà Nội', 'Thành phố Hồ Chí Minh', 'Hồ Chí Minh'];
        $otherProvinces = ['Hải Phòng', 'Cần Thơ', 'An Giang', 'Bà Rịa - Vũng Tàu', 'Bắc Giang', 'Bắc Kạn', 'Bạc Liêu', 'Bắc Ninh', 'Bến Tre', 'Bình Định', 'Bình Dương', 'Bình Phước', 'Bình Thuận', 'Cà Mau', 'Cao Bằng', 'Đắk Lắk', 'Đắk Nông', 'Điện Biên', 'Đồng Nai', 'Đồng Tháp', 'Gia Lai', 'Hà Giang', 'Hà Nam', 'Hà Tĩnh', 'Hải Dương', 'Hậu Giang', 'Hòa Bình', 'Hưng Yên', 'Khánh Hòa', 'Kiên Giang', 'Kon Tum', 'Lai Châu', 'Lâm Đồng', 'Lạng Sơn', 'Lào Cai', 'Long An', 'Nam Định', 'Nghệ An', 'Ninh Bình', 'Ninh Thuận', 'Phú Thọ', 'Phú Yên', 'Quảng Bình', 'Quảng Nam', 'Quảng Ngãi', 'Quảng Ninh', 'Quảng Trị', 'Sóc Trăng', 'Sơn La', 'Tây Ninh', 'Thái Bình', 'Thái Nguyên', 'Thanh Hóa', 'Thừa Thiên Huế', 'Tiền Giang', 'Trà Vinh', 'Tuyên Quang', 'Vĩnh Long', 'Vĩnh Phúc', 'Yên Bái'];
        
        // Tạo 60 đơn hàng để phân bổ đều (mỗi kho khoảng 15-20 đơn)
        $totalOrders = 60;
        
        // Đảm bảo mỗi kho có đủ đơn hàng
        $ordersPerWarehouse = ceil($totalOrders / $warehouses->count());
        
        for ($i = 1; $i <= $totalOrders; $i++) {
            $status = $statuses[array_rand($statuses)];
            $serviceType = $serviceTypes[array_rand($serviceTypes)];
            $pickupDriver = $drivers->random();
            $deliveryDriver = $drivers->random();
            
            // Phân bổ đều giữa các kho: mỗi kho nhận khoảng ordersPerWarehouse đơn
            // QUAN TRỌNG: Giữ nguyên targetWarehouse để đảm bảo mỗi kho có đơn hàng riêng
            $warehouseIndex = floor(($i - 1) / $ordersPerWarehouse) % $warehouses->count();
            $targetWarehouse = $warehouses->values()[$warehouseIndex];
            
            // Xác định đơn hàng được tạo từ kho nào
            // 50% đơn hàng được tạo từ kho khác (không phải targetWarehouse)
            $isCreatedFromOtherWarehouse = rand(0, 100) <= 50;
            $sourceWarehouse = null;
            
            // Xác định kho nguồn (nơi tạo đơn hàng)
            if ($isCreatedFromOtherWarehouse) {
                // Chọn kho khác với targetWarehouse
                $otherWarehousesList = $warehouses->where('id', '!=', $targetWarehouse->id)->values();
                if ($otherWarehousesList->isNotEmpty()) {
                    $sourceWarehouse = $otherWarehousesList->random();
                }
            }
            
            // Nếu không chọn được kho khác, dùng targetWarehouse làm kho nguồn
            $originWarehouse = $sourceWarehouse ?? $targetWarehouse;
            $senderProvince = $originWarehouse->province;
            $senderDistrict = $originWarehouse->district ?? 'Quận ' . rand(1, 12);
            $senderWard = $originWarehouse->ward ?? 'Phường ' . rand(1, 20);
            $customers = Customer::where('warehouse_id', $originWarehouse->id)->get();
            if ($customers->isEmpty()) {
                // Nếu không có khách hàng, lấy ngẫu nhiên
                $customers = Customer::all();
            }
            $customer = $customers->random();
            
            // Chọn tỉnh nhận - QUAN TRỌNG: Kho KHÔNG ship đến cùng tỉnh với kho đó
            $receiverProvince = null;
            $targetProvince = $targetWarehouse->province;
            
            // Loại trừ tỉnh của kho khỏi danh sách tỉnh nhận
            $availableProvinces = array_filter($otherProvinces, function($province) use ($targetProvince) {
                // Loại trừ Hà Nội nếu kho là Hà Nội
                if ($targetProvince === 'Hà Nội' && in_array($province, ['Hà Nội'])) {
                    return false;
                }
                // Loại trừ Sài Gòn nếu kho là Sài Gòn
                if (in_array($targetProvince, ['Thành phố Hồ Chí Minh', 'Hồ Chí Minh']) 
                    && in_array($province, ['Thành phố Hồ Chí Minh', 'Hồ Chí Minh'])) {
                    return false;
                }
                return true;
            });
            
            if ($targetProvince === 'Hà Nội') {
                // Kho Hà Nội: KHÔNG ship đến Hà Nội, chỉ ship đến các tỉnh khác
                // 60% đến Sài Gòn, 40% các tỉnh khác (KHÔNG có Hà Nội)
                $receiverProvince = rand(0, 100) <= 60 
                    ? 'Thành phố Hồ Chí Minh' 
                    : $availableProvinces[array_rand($availableProvinces)];
            } elseif (in_array($targetProvince, ['Thành phố Hồ Chí Minh', 'Hồ Chí Minh'])) {
                // Kho Sài Gòn: KHÔNG ship đến Sài Gòn, chỉ ship đến các tỉnh khác
                // 60% đến Hà Nội, 40% các tỉnh khác (KHÔNG có Sài Gòn)
                $receiverProvince = rand(0, 100) <= 60 
                    ? 'Hà Nội' 
                    : $availableProvinces[array_rand($availableProvinces)];
            } else {
                // Fallback: chọn ngẫu nhiên từ các tỉnh khác
                $receiverProvince = $availableProvinces[array_rand($availableProvinces)];
            }
            
            // Tìm tuyến từ tỉnh gửi đến tỉnh nhận
            $route = $routes->where('from_province', $senderProvince)
                ->where('to_province', $receiverProvince)
                ->first();
            
            // Nếu không có tuyến trực tiếp, tìm tuyến từ Hà Nội hoặc Sài Gòn (trung chuyển)
            if (!$route && $haNoiWarehouse) {
                $route = $routes->where('from_province', 'Hà Nội')
                    ->where('to_province', $receiverProvince)
                    ->first();
            }
            if (!$route && $hcmWarehouse) {
                $route = $routes->where('from_province', 'Thành phố Hồ Chí Minh')
                    ->where('to_province', $receiverProvince)
                    ->first();
            }

            $weight = rand(1, 20) + (rand(0, 99) / 100);
            $codAmount = rand(0, 100) > 50 ? rand(100000, 5000000) : 0;

            // Generate tracking number
            $trackingNumber = 'VD' . date('Ymd') . strtoupper(Str::random(6));
            
            // Xác định warehouse_id và to_warehouse_id dựa trên status
            // QUAN TRỌNG: Đảm bảo mỗi đơn hàng đều có warehouse_id hoặc to_warehouse_id
            // để admin kho có thể xem được đơn hàng của kho mình
            $warehouseId = null;
            $toWarehouseId = null;
            
            // Xác định kho nguồn (nơi đơn hàng được tạo/lấy)
            $originWarehouse = $sourceWarehouse ?? $defaultWarehouse;
            
            // Xác định xem đơn hàng có phải từ kho khác chuyển đến không
            // 25% đơn hàng trong kho đích là từ kho khác chuyển đến
            $isTransferredFromOtherWarehouse = rand(0, 100) <= 25 && 
                in_array($status, ['in_warehouse', 'out_for_delivery']) &&
                $targetWarehouse->id !== $originWarehouse->id;
            
            // Logic phân bổ warehouse_id và to_warehouse_id
            // QUAN TRỌNG: Đảm bảo logic phù hợp với việc phân công tài xế
            if ($status === 'in_transit') {
                // Đơn hàng đang vận chuyển
                if ($targetWarehouse->id !== $originWarehouse->id) {
                    // Đang vận chuyển từ kho nguồn đến kho đích khác
                    $warehouseId = $originWarehouse->id; // Từ kho nguồn
                    $toWarehouseId = $targetWarehouse->id; // Đến kho đích (CẦN intercity_driver)
                } else {
                    // Đang vận chuyển trong cùng kho (hiếm, nhưng có thể)
                    $warehouseId = $originWarehouse->id;
                    $toWarehouseId = null; // Không có to_warehouse_id (CÓ THỂ dùng shipper)
                }
            } elseif (in_array($status, ['in_warehouse', 'out_for_delivery'])) {
                // Đơn hàng đã đến kho đích - KHÔNG BAO GIỜ có to_warehouse_id
                if ($isTransferredFromOtherWarehouse) {
                    // Đơn hàng đã nhận từ kho khác, đang trong kho đích
                    $warehouseId = $targetWarehouse->id;
                    $toWarehouseId = null; // QUAN TRỌNG: Đã đến kho đích, không còn to_warehouse_id
                } else {
                    // Đơn hàng đang trong kho đích (từ tài xế lấy về hoặc tạo tại kho)
                    $warehouseId = $targetWarehouse->id;
                    $toWarehouseId = null; // QUAN TRỌNG: Đã ở kho đích, không có to_warehouse_id
                }
            } elseif (in_array($status, ['picked_up'])) {
                // Đơn hàng vừa lấy - có thể đang về kho hoặc đã về kho
                // 70% đã về kho đích, 30% đang về kho
                if (rand(0, 100) <= 70) {
                    // Đã về kho đích
                    $warehouseId = $targetWarehouse->id;
                } else {
                    // Đang về kho (vẫn ở kho nguồn)
                    $warehouseId = $originWarehouse->id;
                }
                $toWarehouseId = null; // Chưa xuất đi kho khác
            } elseif (in_array($status, ['pending', 'pickup_pending'])) {
                // Đơn hàng mới/chờ lấy, ở kho nguồn
                $warehouseId = $originWarehouse->id;
                $toWarehouseId = null;
            } elseif (in_array($status, ['delivered', 'failed'])) {
                // Đơn hàng đã giao/thất bại, gán cho kho đích để admin kho có thể xem
                $warehouseId = $targetWarehouse->id;
                $toWarehouseId = null; // Đã giao, không còn to_warehouse_id
            }

            // Tạo thời gian ngẫu nhiên trong 7 ngày qua để đơn hàng không giống nhau
            $randomDaysAgo = rand(0, 7);
            $randomHoursAgo = rand(0, 23);
            $randomMinutesAgo = rand(0, 59);
            $createdAt = now()->subDays($randomDaysAgo)->subHours($randomHoursAgo)->subMinutes($randomMinutesAgo);
            
            $order = Order::create([
                'tracking_number' => $trackingNumber,
                'customer_id' => $customer->id,
                'sender_name' => $customer->name,
                'sender_phone' => $customer->phone,
                'sender_address' => $customer->address ?? ($sourceWarehouse ? $sourceWarehouse->address : 'Số 1 Đường Quang Trung, Phường Hưng Bình'),
                'sender_province' => $senderProvince, // Có thể là Nghệ An hoặc tỉnh khác
                'sender_district' => $senderDistrict,
                'sender_ward' => $senderWard,
                'receiver_name' => 'Người nhận ' . $i,
                'receiver_phone' => '09' . rand(10000000, 99999999),
                'receiver_address' => rand(1, 999) . ' Đường ABC, Phường ' . rand(1, 20),
                'receiver_province' => $receiverProvince,
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
                'route_id' => in_array($status, ['in_transit', 'out_for_delivery', 'delivered']) && $route ? $route->id : null,
                'warehouse_id' => $warehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'pickup_scheduled_at' => $status !== 'pending' ? $createdAt->copy()->subDays(rand(1, 5)) : null,
                'picked_up_at' => in_array($status, ['picked_up', 'in_warehouse', 'in_transit', 'out_for_delivery', 'delivered']) ? $createdAt->copy()->subDays(rand(1, 4)) : null,
                'delivery_scheduled_at' => in_array($status, ['out_for_delivery', 'delivered', 'failed']) ? $createdAt->copy()->subDays(rand(0, 2)) : null,
                'delivered_at' => $status === 'delivered' ? $createdAt->copy()->subDays(rand(0, 1)) : null,
                'is_fragile' => rand(0, 100) > 70,
                'notes' => rand(0, 100) > 80 ? 'Ghi chú đặc biệt cho đơn hàng ' . $i : null,
                'created_by' => 1,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Create order statuses - sử dụng created_at của order
            $createdFromText = $sourceWarehouse ? " từ {$sourceWarehouse->name}" : "";
            $statusHistory = [
                ['status' => 'pending', 'notes' => "Đơn hàng mới được tạo{$createdFromText}", 'created_at' => $createdAt],
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
                
                // Xác định kho nhập hàng
                if ($isTransferredFromOtherWarehouse && $status === 'in_warehouse') {
                    // Đơn hàng từ kho khác chuyển đến
                    $warehouseForHistory = $targetWarehouse;
                    $notes = "Đã nhận từ {$originWarehouse->name} - Nhập kho {$targetWarehouse->name}";
                } elseif ($status === 'in_warehouse' && $warehouseId === $targetWarehouse->id) {
                    // Đơn hàng đang trong kho đích (từ tài xế lấy về)
                    $warehouseForHistory = $targetWarehouse;
                    $notes = "Đã nhập kho {$targetWarehouse->name}";
                } else {
                    // Đơn hàng ở kho nguồn
                    $warehouseForHistory = $originWarehouse;
                    $notes = "Đã nhập kho {$originWarehouse->name}";
                }
                
                $statusHistory[] = [
                    'status' => 'in_warehouse',
                    'notes' => $notes,
                    'warehouse_id' => $warehouseForHistory->id,
                    'created_at' => $warehouseTime,
                ];
            }

            if (in_array($status, ['in_transit', 'out_for_delivery', 'delivered', 'failed'])) {
                $transitTime = $order->picked_up_at ? $order->picked_up_at->copy()->addDays(1) : now();
                $statusHistory[] = [
                    'status' => 'in_transit',
                    'notes' => "Đang vận chuyển từ {$originWarehouse->name} đến {$targetWarehouse->name}",
                    'warehouse_id' => $originWarehouse->id,
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
            
            // Tạo warehouse transaction nếu đơn hàng được chuyển từ kho khác
            if ($isTransferredFromOtherWarehouse && in_array($status, ['in_warehouse', 'out_for_delivery'])) {
                // Transaction nhập kho (từ kho khác)
                \App\Models\WarehouseTransaction::create([
                    'warehouse_id' => $targetWarehouse->id,
                    'order_id' => $order->id,
                    'type' => 'in',
                    'transaction_date' => $order->picked_up_at ? $order->picked_up_at->copy()->addDays(1) : now(),
                    'notes' => "Nhận từ {$originWarehouse->name} ({$originWarehouse->province})",
                    'created_by' => 1,
                ]);
                
                // Transaction xuất kho (từ kho nguồn)
                \App\Models\WarehouseTransaction::create([
                    'warehouse_id' => $originWarehouse->id,
                    'order_id' => $order->id,
                    'type' => 'out',
                    'transaction_date' => $order->picked_up_at ? $order->picked_up_at->copy()->addDays(1)->subHours(2) : now(),
                    'notes' => "Xuất kho vận chuyển đến {$targetWarehouse->name}",
                    'created_by' => 1,
                ]);
            } elseif (in_array($status, ['in_warehouse', 'out_for_delivery']) && $warehouseId) {
                // Đơn hàng từ tài xế lấy về, tạo transaction nhập kho
                $warehouse = \App\Models\Warehouse::find($warehouseId);
                if ($warehouse) {
                    \App\Models\WarehouseTransaction::create([
                        'warehouse_id' => $warehouse->id,
                        'order_id' => $order->id,
                        'type' => 'in',
                        'transaction_date' => $order->picked_up_at ? $order->picked_up_at->copy()->addHours(2) : now(),
                        'notes' => $order->pickup_driver_id ? 'Tự động nhập kho sau khi tài xế lấy hàng' : 'Người gửi đưa hàng đến kho',
                        'created_by' => 1,
                    ]);
                }
            }
        }
    }
}
