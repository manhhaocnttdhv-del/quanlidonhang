<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Driver;
use App\Models\Warehouse;
use App\Models\WarehouseTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DispatchController extends Controller
{
    /**
     * Display dispatch index page
     */
    public function index(Request $request)
    {
        // Đơn hàng chờ phân công
        $pendingOrders = Order::where('status', 'pending')
            ->with(['customer', 'route', 'pickupDriver'])
            ->orderBy('created_at', 'asc')
            ->get();
        
        $user = auth()->user();
        
        // Đơn hàng chờ phân công - lọc theo kho
        $pendingOrdersQuery = Order::where('status', 'pending')
            ->with(['customer', 'route', 'pickupDriver']);
        
        // Warehouse admin chỉ xem đơn hàng của kho mình
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $pendingOrdersQuery->where('warehouse_id', $user->warehouse_id);
        }
        
        $pendingOrders = $pendingOrdersQuery->orderBy('created_at', 'asc')->get();
        
        // Đơn hàng đã phân công tài xế (pickup_pending, picking_up, picked_up)
        // KHÔNG bao gồm đơn hàng đã về kho (in_warehouse) - đơn hàng đã về kho chỉ hiển thị trong "Kho của tôi"
        $assignedOrdersQuery = Order::whereIn('status', ['pickup_pending', 'picking_up', 'picked_up'])
            ->whereNotNull('pickup_driver_id') // Phải có tài xế được phân công
            ->with(['customer', 'route', 'pickupDriver']);
        
        // Warehouse admin chỉ xem đơn hàng của kho mình
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $assignedOrdersQuery->where(function($query) use ($user) {
                // Đơn hàng trong kho của mình hoặc đang được tài xế của kho mình lấy
                $query->where('warehouse_id', $user->warehouse_id)
                      ->orWhereHas('pickupDriver', function($q) use ($user) {
                          $q->where('warehouse_id', $user->warehouse_id);
                      });
            });
        }
        
        $assignedOrders = $assignedOrdersQuery->orderByRaw("CASE 
                WHEN status = 'pickup_pending' THEN 1 
                WHEN status = 'picking_up' THEN 2 
                WHEN status = 'picked_up' THEN 3 
                ELSE 4 
            END")
            ->orderBy('pickup_scheduled_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
            
        // Lấy tài xế theo kho
        $driversQuery = Driver::where('is_active', true);
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $driversQuery->where('warehouse_id', $user->warehouse_id);
        }
        $drivers = $driversQuery->get();
        
        if ($request->expectsJson()) {
            return response()->json([
                'pending' => $pendingOrders,
                'assigned' => $assignedOrders,
            ]);
        }
        
        return view('admin.dispatch.index', compact('pendingOrders', 'assignedOrders', 'drivers'));
    }
    
    /**
     * Get orders pending pickup
     */
    public function pendingPickups(Request $request)
    {
        $query = Order::whereIn('status', ['pending', 'pickup_pending'])
            ->with(['customer', 'route']);

        if ($request->has('area')) {
            $query->where('sender_district', $request->area);
        }

        $orders = $query->orderBy('created_at', 'asc')->paginate(20);

        return response()->json($orders);
    }

    /**
     * Assign driver for pickup
     */
    public function assignPickupDriver(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => 'required',
            'driver_id' => 'required|exists:drivers,id',
            'pickup_scheduled_at' => 'nullable|date',
        ]);
        
        // Parse JSON if order_ids is a string
        $orderIds = is_string($validated['order_ids']) 
            ? json_decode($validated['order_ids'], true) 
            : (is_array($validated['order_ids']) ? $validated['order_ids'] : []);
        
        if (!is_array($orderIds) || empty($orderIds)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Vui lòng chọn ít nhất một đơn hàng'], 400);
            }
            return redirect()->back()->with('error', 'Vui lòng chọn ít nhất một đơn hàng');
        }
        
        // Validate order IDs exist
        foreach ($orderIds as $orderId) {
            if (!Order::where('id', $orderId)->exists()) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => "Đơn hàng ID {$orderId} không tồn tại"], 400);
                }
                return redirect()->back()->with('error', "Đơn hàng ID {$orderId} không tồn tại");
            }
        }

        $driver = Driver::findOrFail($validated['driver_id']);

        $orders = Order::whereIn('id', $orderIds)->get();

        foreach ($orders as $order) {
            $order->update([
                'pickup_driver_id' => $validated['driver_id'],
                'status' => 'pickup_pending',
                'pickup_scheduled_at' => $validated['pickup_scheduled_at'] ?? now(),
            ]);

            OrderStatus::create([
                'order_id' => $order->id,
                'status' => 'pickup_pending',
                'notes' => "Đã phân công tài xế {$driver->name} lấy hàng",
                'driver_id' => $validated['driver_id'],
                'updated_by' => auth()->id(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đã phân công tài xế lấy hàng',
                'data' => $orders,
            ]);
        }
        
        return redirect()->route('admin.dispatch.index')->with('success', 'Đã phân công tài xế lấy hàng');
    }

    /**
     * Update pickup status
     */
    public function updatePickupStatus(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:picking_up,picked_up',
            'notes' => 'nullable|string',
        ]);

        if ($validated['status'] === 'picking_up') {
            // Tài xế đang đi lấy hàng
            $order->update([
                'status' => 'picking_up',
            ]);

            OrderStatus::create([
                'order_id' => $order->id,
                'status' => 'picking_up',
                'notes' => $validated['notes'] ?? 'Tài xế đang đi lấy hàng',
                'driver_id' => $order->pickup_driver_id,
                'updated_by' => auth()->id(),
            ]);
        } elseif ($validated['status'] === 'picked_up') {
            // Tài xế đã lấy hàng - Tự động nhập kho của tài xế
            $driver = Driver::with('warehouse')->find($order->pickup_driver_id);
            
            // Xác định kho nhận hàng: ưu tiên kho của tài xế, nếu không có thì dùng kho mặc định
            $targetWarehouse = null;
            if ($driver && $driver->warehouse) {
                // Tài xế có kho, đưa hàng về kho của tài xế
                $targetWarehouse = $driver->warehouse;
            } else {
                // Tài xế không có kho, dùng kho mặc định
                $targetWarehouse = Warehouse::getDefaultWarehouse();
            }
            
            if (!$targetWarehouse) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Không tìm thấy kho để nhập hàng'], 400);
                }
                return redirect()->back()->with('error', 'Không tìm thấy kho để nhập hàng');
            }

            // Cập nhật đơn hàng: đã lấy hàng và về kho
            $order->update([
                'picked_up_at' => now(),
                'status' => 'in_warehouse',
                'warehouse_id' => $targetWarehouse->id,
                'to_warehouse_id' => null, // Đảm bảo không còn to_warehouse_id khi đã về kho
            ]);

            // Tạo giao dịch nhập kho
            $driverName = $driver ? $driver->name : 'N/A';
            $transactionNotes = $validated['notes'] ?? "Tự động nhập kho {$targetWarehouse->name} sau khi tài xế {$driverName} lấy hàng";
            
            WarehouseTransaction::create([
                'warehouse_id' => $targetWarehouse->id,
                'order_id' => $order->id,
                'type' => 'in',
                'reference_number' => 'AUTO-' . date('YmdHis') . '-' . $order->tracking_number,
                'notes' => $transactionNotes,
                'transaction_date' => now(),
                'created_by' => auth()->id(),
            ]);

            // Tạo trạng thái đơn hàng
            OrderStatus::create([
                'order_id' => $order->id,
                'status' => 'in_warehouse',
                'notes' => $validated['notes'] ?? "Đã lấy hàng và tự động nhập kho {$targetWarehouse->name}",
                'driver_id' => $order->pickup_driver_id,
                'warehouse_id' => $targetWarehouse->id,
                'updated_by' => auth()->id(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Trạng thái lấy hàng đã được cập nhật',
                'data' => $order->fresh(['warehouse', 'pickupDriver']),
            ]);
        }
        
        return redirect()->route('admin.dispatch.index')->with('success', 'Trạng thái lấy hàng đã được cập nhật');
    }

    /**
     * Auto assign drivers randomly for pickup
     */
    public function autoAssignPickupDriver(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => 'required',
            'pickup_scheduled_at' => 'nullable|date',
        ]);
        
        // Parse JSON if order_ids is a string
        $orderIds = is_string($validated['order_ids']) 
            ? json_decode($validated['order_ids'], true) 
            : (is_array($validated['order_ids']) ? $validated['order_ids'] : []);
        
        if (!is_array($orderIds) || empty($orderIds)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Vui lòng chọn ít nhất một đơn hàng'], 400);
            }
            return redirect()->back()->with('error', 'Vui lòng chọn ít nhất một đơn hàng');
        }
        
        $user = auth()->user();
        
        // Get active drivers - lọc theo kho
        $driversQuery = Driver::where('is_active', true);
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $driversQuery->where('warehouse_id', $user->warehouse_id);
        }
        $drivers = $driversQuery->get();
        
        if ($drivers->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Không có tài xế nào khả dụng'], 400);
            }
            return redirect()->back()->with('error', 'Không có tài xế nào khả dụng');
        }
        
        // Get orders
        $orders = Order::whereIn('id', $orderIds)->get();
        
        $assignedCount = 0;
        $driverArray = $drivers->toArray();
        
        foreach ($orders as $order) {
            // Random select a driver
            $randomDriver = $driverArray[array_rand($driverArray)];
            
            $order->update([
                'pickup_driver_id' => $randomDriver['id'],
                'status' => 'pickup_pending',
                'pickup_scheduled_at' => $validated['pickup_scheduled_at'] ?? now(),
            ]);

            OrderStatus::create([
                'order_id' => $order->id,
                'status' => 'pickup_pending',
                'notes' => "Đã tự động phân công tài xế {$randomDriver['name']} lấy hàng",
                'driver_id' => $randomDriver['id'],
                'updated_by' => auth()->id(),
            ]);
            
            $assignedCount++;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Đã tự động phân công tài xế cho {$assignedCount} đơn hàng",
                'data' => $orders->fresh(),
            ]);
        }
        
        return redirect()->route('admin.dispatch.index')->with('success', "Đã tự động phân công tài xế cho {$assignedCount} đơn hàng");
    }

    /**
     * Get available drivers by area
     */
    public function getAvailableDrivers(Request $request)
    {
        $user = auth()->user();
        $area = $request->get('area');

        $query = Driver::where('is_active', true);
        
        // Warehouse admin chỉ xem tài xế của kho mình
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $query->where('warehouse_id', $user->warehouse_id);
        }

        if ($area) {
            $query->where('area', $area);
        }

        $drivers = $query->get();

        return response()->json($drivers);
    }
}
