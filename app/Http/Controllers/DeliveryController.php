<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    /**
     * Display delivery index page
     */
    public function index(Request $request)
    {
        // Đơn hàng đã xuất kho - đang vận chuyển (in_transit)
        $ordersInTransitQuery = Order::where('status', 'in_transit')
            ->with(['customer', 'deliveryDriver', 'warehouse', 'route']);
        
        // Filter theo tỉnh nếu có
        if ($request->has('province_in_transit') && $request->province_in_transit) {
            $ordersInTransitQuery->where('receiver_province', $request->province_in_transit);
        }
        
        $ordersInTransit = $ordersInTransitQuery->orderBy('created_at', 'desc')->get();
        
        // Đơn hàng sẵn sàng giao (đã phân công tài xế)
        $ordersReadyForDeliveryQuery = Order::where('status', 'out_for_delivery')
            ->with(['customer', 'deliveryDriver', 'warehouse']);
        
        // Filter theo tỉnh nếu có
        if ($request->has('province_delivery') && $request->province_delivery) {
            $ordersReadyForDeliveryQuery->where('receiver_province', $request->province_delivery);
        }
        
        $ordersReadyForDelivery = $ordersReadyForDeliveryQuery->orderBy('delivery_scheduled_at', 'asc')->get();
        
        // Tất cả đơn hàng cần giao (bao gồm cả đang vận chuyển)
        $allOrders = Order::whereIn('status', ['in_warehouse', 'in_transit', 'out_for_delivery'])
            ->with(['customer', 'deliveryDriver', 'warehouse', 'route'])
            ->orderByRaw("CASE 
                WHEN status = 'out_for_delivery' THEN 1 
                WHEN status = 'in_transit' THEN 2 
                ELSE 3 
            END")
            ->orderBy('delivery_scheduled_at', 'asc')
            ->get();
            
        $drivers = \App\Models\Driver::where('is_active', true)->get();
        
        $stats = [
            'in_transit' => Order::where('status', 'in_transit')->count(),
            'out_for_delivery' => Order::where('status', 'out_for_delivery')->count(),
            'delivered_today' => Order::where('status', 'delivered')
                ->whereDate('delivered_at', today())
                ->count(),
            'failed_today' => Order::where('status', 'failed')
                ->whereDate('updated_at', today())
                ->count(),
            'pending_delivery' => Order::whereIn('status', ['in_warehouse', 'in_transit'])->count(),
        ];
        
        if ($request->expectsJson()) {
            return response()->json($allOrders);
        }
        
        return view('admin.delivery.index', compact('ordersInTransit', 'ordersReadyForDelivery', 'allOrders', 'drivers', 'stats'));
    }
    
    /**
     * Get orders ready for delivery
     */
    public function readyForDelivery(Request $request)
    {
        $query = Order::whereIn('status', ['in_warehouse', 'in_transit', 'out_for_delivery'])
            ->with(['customer', 'deliveryDriver', 'warehouse']);

        if ($request->has('driver_id')) {
            $query->where('delivery_driver_id', $request->driver_id);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $orders = $query->orderBy('delivery_scheduled_at', 'asc')->paginate(20);

        return response()->json($orders);
    }

    /**
     * Assign delivery driver
     */
    public function assignDeliveryDriver(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'delivery_scheduled_at' => 'nullable|date',
        ]);

        $order->update([
            'delivery_driver_id' => $validated['driver_id'],
            'status' => 'out_for_delivery',
            'delivery_scheduled_at' => $validated['delivery_scheduled_at'] ?? now(),
        ]);

        OrderStatus::create([
            'order_id' => $order->id,
            'status' => 'out_for_delivery',
            'notes' => 'Đã phân công tài xế giao hàng',
            'driver_id' => $validated['driver_id'],
            'updated_by' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đã phân công tài xế giao hàng',
                'data' => $order->fresh(),
            ]);
        }
        
        return redirect()->back()->with('success', 'Đã phân công tài xế giao hàng');
    }
    
    /**
     * Bulk assign delivery driver for multiple orders
     */
    public function bulkAssignDeliveryDriver(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id',
            'driver_id' => 'required|exists:drivers,id',
            'delivery_scheduled_at' => 'nullable|date',
        ]);

        $orderIds = $validated['order_ids'];
        $driverId = $validated['driver_id'];
        $scheduledAt = $validated['delivery_scheduled_at'] ?? now();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($orderIds as $orderId) {
            try {
                $order = Order::findOrFail($orderId);

                // Chỉ phân công cho đơn hàng đang vận chuyển
                if ($order->status !== 'in_transit') {
                    $failedCount++;
                    $errors[] = "Đơn hàng #{$order->tracking_number} không ở trạng thái 'Đang vận chuyển'";
                    continue;
                }

                $order->update([
                    'delivery_driver_id' => $driverId,
                    'status' => 'out_for_delivery',
                    'delivery_scheduled_at' => $scheduledAt,
                ]);

                OrderStatus::create([
                    'order_id' => $order->id,
                    'status' => 'out_for_delivery',
                    'notes' => 'Đã phân công tài xế giao hàng (hàng loạt)',
                    'driver_id' => $driverId,
                    'updated_by' => auth()->id(),
                ]);

                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Lỗi khi phân công đơn hàng #{$orderId}: " . $e->getMessage();
            }
        }

        if ($request->expectsJson()) {
            $response = [
                'message' => "Đã phân công tài xế cho {$successCount} đơn hàng" . ($failedCount > 0 ? ", {$failedCount} đơn thất bại" : ''),
                'data' => ['success' => $successCount, 'failed' => $failedCount],
            ];
            
            if (!empty($errors)) {
                $response['errors'] = $errors;
            }
            
            return response()->json($response);
        }
        
        return redirect()->back()->with('success', "Đã phân công tài xế cho {$successCount} đơn hàng" . ($failedCount > 0 ? ", {$failedCount} đơn thất bại" : ''));
    }

    /**
     * Update delivery status
     */
    public function updateDeliveryStatus(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:delivered,failed',
            'delivery_notes' => 'nullable|string',
            'failure_reason' => 'required_if:status,failed|string',
            'cod_collected' => 'nullable|numeric|min:0',
        ]);

        $order->update([
            'status' => $validated['status'],
            'delivery_notes' => $validated['delivery_notes'] ?? null,
            'failure_reason' => $validated['failure_reason'] ?? null,
        ]);

        if ($validated['status'] === 'delivered') {
            $order->update(['delivered_at' => now()]);
        }

        OrderStatus::create([
            'order_id' => $order->id,
            'status' => $validated['status'],
            'notes' => $validated['delivery_notes'] ?? ($validated['failure_reason'] ?? null),
            'driver_id' => $order->delivery_driver_id,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Trạng thái giao hàng đã được cập nhật',
            'data' => $order->fresh(),
        ]);
    }

    /**
     * Get delivery statistics for driver
     */
    public function getDriverStatistics(Request $request)
    {
        $driverId = $request->get('driver_id', auth()->id());

        $stats = [
            'today_delivered' => Order::where('delivery_driver_id', $driverId)
                ->where('status', 'delivered')
                ->whereDate('delivered_at', today())
                ->count(),
            'today_failed' => Order::where('delivery_driver_id', $driverId)
                ->where('status', 'failed')
                ->whereDate('updated_at', today())
                ->count(),
            'pending_deliveries' => Order::where('delivery_driver_id', $driverId)
                ->whereIn('status', ['out_for_delivery', 'in_transit'])
                ->count(),
        ];

        return response()->json($stats);
    }
}
