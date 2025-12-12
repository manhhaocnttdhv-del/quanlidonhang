<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Order;
use App\Models\WarehouseTransaction;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Tự động redirect đến kho mặc định (Nghệ An)
        $defaultWarehouse = Warehouse::getDefaultWarehouse();
        
        if ($defaultWarehouse && !$request->expectsJson()) {
            return redirect()->route('admin.warehouses.show', $defaultWarehouse->id);
        }
        
        // Nếu không có kho mặc định, hiển thị danh sách
        $warehouses = Warehouse::where('is_active', true)
            ->orderByRaw("CASE WHEN province = 'Nghệ An' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
        
        if ($request->expectsJson()) {
            return response()->json($warehouses);
        }
        
        return view('admin.warehouses.index', compact('warehouses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:warehouses,code',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'province' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'ward' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'manager_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $warehouse = Warehouse::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Kho đã được tạo thành công',
                'data' => $warehouse,
            ], 201);
        }
        
        return redirect()->route('admin.warehouses.index')->with('success', 'Kho đã được tạo thành công');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $warehouse = Warehouse::with(['orders', 'drivers'])->findOrFail($id);
        
        // Tất cả đơn hàng trong kho
        $allOrdersQuery = Order::where('warehouse_id', $id)
            ->where('status', 'in_warehouse')
            ->with(['customer', 'route', 'pickupDriver']);
        
        // Filter theo tỉnh nếu có
        if ($request->has('province') && $request->province) {
            $allOrdersQuery->where('receiver_province', $request->province);
        }
        
        $allOrders = $allOrdersQuery->orderBy('picked_up_at', 'desc')->get();
        
        // Phân loại đơn hàng
        $ordersFromPickup = []; // Đơn hàng tài xế lấy từ người gửi
        $ordersFromOtherWarehouses = []; // Đơn hàng từ kho khác gửi về
        
        foreach ($allOrders as $order) {
            // Kiểm tra transaction nhập kho gần nhất
            $lastInTransaction = WarehouseTransaction::where('warehouse_id', $id)
                ->where('order_id', $order->id)
                ->where('type', 'in')
                ->orderBy('transaction_date', 'desc')
                ->first();
            
            // Nếu có picked_up_at và transaction ghi "Tự động nhập kho sau khi tài xế lấy hàng" 
            // hoặc có pickup_driver_id => đơn hàng từ tài xế lấy về
            if ($order->picked_up_at && $order->pickup_driver_id) {
                $ordersFromPickup[] = $order;
            } else {
                // Đơn hàng từ kho khác gửi về
                $ordersFromOtherWarehouses[] = $order;
            }
        }
        
        $inventory = [
            'orders' => $allOrders,
            'orders_from_pickup' => collect($ordersFromPickup),
            'orders_from_other_warehouses' => collect($ordersFromOtherWarehouses),
            'total_orders' => $allOrders->count(),
            'today_in' => WarehouseTransaction::where('warehouse_id', $id)
                ->where('type', 'in')
                ->whereDate('transaction_date', today())
                ->count(),
            'today_out' => WarehouseTransaction::where('warehouse_id', $id)
                ->where('type', 'out')
                ->whereDate('transaction_date', today())
                ->count(),
            // Hàng vừa nhận từ tài xế hôm nay
            'today_received_from_pickup' => Order::where('warehouse_id', $id)
                ->where('status', 'in_warehouse')
                ->whereDate('picked_up_at', today())
                ->whereNotNull('pickup_driver_id')
                ->with(['pickupDriver', 'customer'])
                ->orderBy('picked_up_at', 'desc')
                ->get(),
            // TẤT CẢ đơn hàng từ tài xế lấy từ khách hàng (bao gồm cả đang lấy và đã lấy)
            // Điều kiện: có pickup_driver_id (đã phân công tài xế) VÀ (đã lấy hàng HOẶC đang trong kho)
            'all_orders_from_pickup' => Order::where('warehouse_id', $id)
                ->where(function($query) {
                    $query->where('status', 'in_warehouse')
                          ->orWhereIn('status', ['pickup_pending', 'picking_up', 'picked_up']);
                })
                ->whereNotNull('pickup_driver_id')
                ->when($request->has('province') && $request->province, function($q) use ($request) {
                    $q->where('receiver_province', $request->province);
                })
                ->with(['pickupDriver', 'customer', 'route'])
                ->orderByRaw('CASE 
                    WHEN picked_up_at IS NOT NULL THEN picked_up_at 
                    WHEN pickup_scheduled_at IS NOT NULL THEN pickup_scheduled_at 
                    ELSE created_at 
                END DESC')
                ->get(),
            // Hàng vừa nhận từ kho khác hôm nay
            'today_received_from_warehouses' => WarehouseTransaction::where('warehouse_id', $id)
                ->where('type', 'in')
                ->whereDate('transaction_date', today())
                ->where('notes', 'like', '%kho%')
                ->where('notes', 'not like', '%tài xế%')
                ->where('notes', 'not like', '%Tự động nhập kho sau khi tài xế%')
                ->with(['order.customer'])
                ->orderBy('transaction_date', 'desc')
                ->get(),
        ];
        
        $routes = \App\Models\Route::where('is_active', true)->get();
        
        // Lấy danh sách tuyến từ Nghệ An đến các tỉnh
        $routesFromNgheAn = \App\Models\Route::where('is_active', true)
            ->where('from_province', 'Nghệ An')
            ->get()
            ->keyBy('to_province'); // Key by to_province để dễ tìm
        
        if ($request->expectsJson()) {
            return response()->json($warehouse);
        }
        
        return view('admin.warehouses.show', compact('warehouse', 'inventory', 'routes', 'routesFromNgheAn'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string',
            'phone' => 'nullable|string|max:20',
            'manager_name' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        $warehouse->update($validated);

        return response()->json([
            'message' => 'Kho đã được cập nhật',
            'data' => $warehouse->fresh(),
        ]);
    }

    /**
     * Receive order into warehouse
     */
    public function receiveOrder(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'order_id' => 'required|exists:orders,id',
            'from_warehouse_id' => 'nullable|exists:warehouses,id',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $order = Order::findOrFail($validated['order_id']);

        // Use default warehouse (Nghệ An) if not specified
        $warehouseId = $validated['warehouse_id'] ?? Warehouse::getDefaultWarehouse()->id ?? null;
        
        // Lấy thông tin kho gửi (nếu có)
        $fromWarehouse = null;
        if ($validated['from_warehouse_id'] ?? null) {
            $fromWarehouse = Warehouse::find($validated['from_warehouse_id']);
        }

        // Tạo ghi chú
        $notes = $validated['notes'] ?? '';
        if ($fromWarehouse) {
            $notes = ($notes ? $notes . ' - ' : '') . "Nhận từ {$fromWarehouse->name} ({$fromWarehouse->province})";
        } elseif (!$notes) {
            $notes = 'Nhận từ kho khác';
        }

        $order->update([
            'warehouse_id' => $warehouseId,
            'status' => 'in_warehouse',
        ]);

        WarehouseTransaction::create([
            'warehouse_id' => $warehouseId,
            'order_id' => $validated['order_id'],
            'type' => 'in',
            'reference_number' => $validated['reference_number'] ?? null,
            'notes' => $notes,
            'transaction_date' => now(),
            'created_by' => auth()->id(),
        ]);

        // Tạo trạng thái đơn hàng
        \App\Models\OrderStatus::create([
            'order_id' => $order->id,
            'status' => 'in_warehouse',
            'notes' => $notes,
            'warehouse_id' => $warehouseId,
            'updated_by' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đơn hàng đã được nhập kho',
                'data' => $order->fresh(),
            ]);
        }
        
        return redirect()->back()->with('success', 'Đơn hàng đã được nhập kho');
    }

    /**
     * Release order from warehouse
     */
    public function releaseOrder(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'order_id' => 'required|exists:orders,id',
            'route_id' => 'nullable|exists:routes,id',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $order = Order::findOrFail($validated['order_id']);

        // Check if order is in warehouse
        if ($order->status !== 'in_warehouse') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Đơn hàng không ở trong kho'], 400);
            }
            return redirect()->back()->with('error', 'Đơn hàng không ở trong kho');
        }

        // Use default warehouse (Nghệ An) if not specified
        $warehouseId = $validated['warehouse_id'] ?? $order->warehouse_id ?? Warehouse::getDefaultWarehouse()->id ?? null;

        // Tự động tìm tuyến từ Nghệ An đến tỉnh nhận nếu chưa chọn
        $routeId = $validated['route_id'] ?? null;
        if (!$routeId && $order->receiver_province) {
            $route = \App\Models\Route::where('is_active', true)
                ->where('from_province', 'Nghệ An')
                ->where('to_province', $order->receiver_province)
                ->first();
            if ($route) {
                $routeId = $route->id;
            }
        }

        if ($routeId) {
            $order->update(['route_id' => $routeId]);
        }

        $order->update(['status' => 'in_transit']);

        // Create order status
        \App\Models\OrderStatus::create([
            'order_id' => $order->id,
            'status' => 'in_transit',
            'notes' => $validated['notes'] ?? 'Đã xuất kho, đang vận chuyển',
            'warehouse_id' => $warehouseId,
            'updated_by' => auth()->id(),
        ]);

        WarehouseTransaction::create([
            'warehouse_id' => $warehouseId,
            'order_id' => $validated['order_id'],
            'type' => 'out',
            'route_id' => $validated['route_id'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'transaction_date' => now(),
            'created_by' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đơn hàng đã được xuất kho',
                'data' => $order->fresh(),
            ]);
        }
        
        return redirect()->back()->with('success', 'Đơn hàng đã được xuất kho');
    }

    /**
     * Bulk release orders from warehouse
     */
    public function bulkReleaseOrder(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'order_ids' => 'required',
            'route_id' => 'nullable|exists:routes,id',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
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

        // Use default warehouse (Nghệ An) if not specified
        $warehouseId = $validated['warehouse_id'] ?? Warehouse::getDefaultWarehouse()->id ?? null;

        $orders = Order::whereIn('id', $orderIds)
            ->where('status', 'in_warehouse')
            ->get();

        if ($orders->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Không có đơn hàng nào hợp lệ để xuất kho'], 400);
            }
            return redirect()->back()->with('error', 'Không có đơn hàng nào hợp lệ để xuất kho');
        }

        $successCount = 0;
        $failedCount = 0;
        $globalRouteId = $validated['route_id'] ?? null; // Tuyến chung nếu chọn

        foreach ($orders as $order) {
            try {
                // Ưu tiên tuyến chung, nếu không có thì tự động tìm tuyến từ Nghệ An đến tỉnh nhận
                $routeId = $globalRouteId;
                if (!$routeId && $order->receiver_province) {
                    $route = \App\Models\Route::where('is_active', true)
                        ->where('from_province', 'Nghệ An')
                        ->where('to_province', $order->receiver_province)
                        ->first();
                    if ($route) {
                        $routeId = $route->id;
                    }
                }

                if ($routeId) {
                    $order->update(['route_id' => $routeId]);
                }

                $order->update(['status' => 'in_transit']);

                // Create order status
                \App\Models\OrderStatus::create([
                    'order_id' => $order->id,
                    'status' => 'in_transit',
                    'notes' => $validated['notes'] ?? 'Đã xuất kho hàng loạt, đang vận chuyển',
                    'warehouse_id' => $warehouseId,
                    'updated_by' => auth()->id(),
                ]);

                WarehouseTransaction::create([
                    'warehouse_id' => $warehouseId,
                    'order_id' => $order->id,
                    'type' => 'out',
                    'route_id' => $routeId ?? null,
                    'reference_number' => $validated['reference_number'] ?? null,
                    'notes' => $validated['notes'] ?? 'Xuất kho hàng loạt',
                    'transaction_date' => now(),
                    'created_by' => auth()->id(),
                ]);

                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Đã xuất kho {$successCount} đơn hàng" . ($failedCount > 0 ? ", {$failedCount} đơn thất bại" : ''),
                'data' => ['success' => $successCount, 'failed' => $failedCount],
            ]);
        }
        
        return redirect()->back()->with('success', "Đã xuất kho {$successCount} đơn hàng" . ($failedCount > 0 ? ", {$failedCount} đơn thất bại" : ''));
    }

    /**
     * Get warehouse inventory
     */
    public function getInventory(string $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $orders = Order::where('warehouse_id', $id)
            ->where('status', 'in_warehouse')
            ->with(['customer'])
            ->get();

        return response()->json([
            'warehouse' => $warehouse,
            'orders' => $orders,
            'total_orders' => $orders->count(),
        ]);
    }

    /**
     * Get warehouse transactions
     */
    public function getTransactions(string $id, Request $request)
    {
        $query = WarehouseTransaction::where('warehouse_id', $id)
            ->with(['order', 'route', 'createdBy']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->paginate(20);

        return response()->json($transactions);
    }
}
