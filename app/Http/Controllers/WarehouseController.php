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
        $user = auth()->user();

        // Super admin và admin xem tất cả kho
        if ($user->canManageWarehouses()) {
            $warehouses = Warehouse::where('is_active', true)
                ->orderByRaw("CASE WHEN province = 'Nghệ An' THEN 0 ELSE 1 END")
                ->orderBy('name')
                ->get();
        } else {
            // Warehouse admin chỉ xem kho của mình
            $warehouses = Warehouse::where('id', $user->warehouse_id)
                ->where('is_active', true)
                ->get();
            
            // Nếu chỉ có 1 kho, tự động redirect
            if ($warehouses->count() === 1 && !$request->expectsJson()) {
                return redirect()->route('admin.warehouses.show', $warehouses->first()->id);
            }
        }

        // Tự động redirect đến kho mặc định (Nghệ An) nếu là super admin/admin
        if ($user->canManageWarehouses() && !$request->expectsJson()) {
            $defaultWarehouse = Warehouse::getDefaultWarehouse();
            if ($defaultWarehouse) {
                return redirect()->route('admin.warehouses.show', $defaultWarehouse->id);
            }
        }
        
        if ($request->expectsJson()) {
            return response()->json($warehouses);
        }
        
        return view('admin.warehouses.index', compact('warehouses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Chỉ super admin và admin mới được tạo kho
        if (!auth()->user()->canManageWarehouses()) {
            return redirect()->route('admin.warehouses.index')->with('error', 'Bạn không có quyền tạo kho');
        }

        // Lấy danh sách users có thể làm admin kho (chưa có kho hoặc không phải warehouse_admin)
        $availableUsers = \App\Models\User::where('is_active', true)
            ->where(function($query) {
                $query->whereNull('warehouse_id')
                      ->orWhere('role', '!=', 'warehouse_admin');
            })
            ->where('role', '!=', 'super_admin')
            ->orderBy('name')
            ->get();

        // Lấy danh sách tỉnh/thành phố từ database
        $provinces = \App\Models\Province::orderBy('name')->get();

        return view('admin.warehouses.create', compact('availableUsers', 'provinces'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Kiểm tra quyền: chỉ super_admin và admin mới được tạo kho
        if (!auth()->user()->canManageWarehouses()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Bạn không có quyền tạo kho'], 403);
            }
            return redirect()->back()->with('error', 'Bạn không có quyền tạo kho');
        }

        $validated = $request->validate([
            'code' => 'required|string|unique:warehouses,code',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'province' => 'required|string|max:255', // Tên tỉnh/thành phố
            'province_code' => 'nullable|string|max:10', // Mã tỉnh/thành phố
            'ward' => 'nullable|string|max:255', // Tên phường/xã
            'ward_code' => 'nullable|string|max:20', // Mã phường/xã
            'phone' => 'nullable|string|max:20',
            'manager_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'admin_user_id' => 'nullable|exists:users,id', // ID của user sẽ làm admin kho
        ]);

        $warehouse = Warehouse::create($validated);

        // Nếu có chọn admin, gán user làm admin kho và cập nhật tên quản lý
        if (!empty($validated['admin_user_id'])) {
            $adminUser = \App\Models\User::findOrFail($validated['admin_user_id']);
            $adminUser->update([
                'role' => 'warehouse_admin',
                'warehouse_id' => $warehouse->id,
            ]);
            
            // Cập nhật tên quản lý = tên admin kho
            $warehouse->update([
                'manager_name' => $adminUser->name
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Kho đã được tạo thành công',
                'data' => $warehouse,
            ], 201);
        }
        
        $message = 'Kho đã được tạo thành công';
        if (!empty($validated['admin_user_id'])) {
            $message .= ' và đã gán admin kho';
        }
        return redirect()->route('admin.warehouses.show', $warehouse->id)->with('success', $message);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $warehouse = Warehouse::with(['orders', 'drivers'])->findOrFail($id);
        
        // Đơn hàng đang đến kho (in_transit với to_warehouse_id = kho này)
        $ordersIncoming = Order::where('to_warehouse_id', $id)
            ->where('status', 'in_transit')
            ->with(['customer', 'route', 'warehouse', 'pickupDriver'])
            ->orderBy('updated_at', 'desc')
            ->get();
        
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
        
        // Lấy danh sách kho khác (để chọn khi xuất hàng đi kho khác)
        $otherWarehouses = Warehouse::where('id', '!=', $id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Lấy danh sách tài xế vận chuyển tỉnh (để chọn khi xuất hàng đi kho khác)
        $intercityDrivers = \App\Models\Driver::where('driver_type', 'intercity_driver')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        if ($request->expectsJson()) {
            return response()->json($warehouse);
        }
        
        return view('admin.warehouses.show', compact('warehouse', 'inventory', 'routes', 'routesFromNgheAn', 'ordersIncoming', 'otherWarehouses', 'intercityDrivers'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        // Kiểm tra quyền
        $user = auth()->user();
        if (!$user->canManageWarehouses() && !$user->isAdminOfWarehouse($warehouse->id)) {
            return redirect()->route('admin.warehouses.index')->with('error', 'Bạn không có quyền sửa kho này');
        }

        // Load danh sách tỉnh/thành phố từ database
        $provinces = \App\Models\Province::orderBy('name')->get();

        return view('admin.warehouses.edit', compact('warehouse', 'provinces'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        // Kiểm tra quyền: super_admin/admin có thể sửa tất cả, warehouse_admin chỉ sửa được kho của mình
        $user = auth()->user();
        if (!$user->canManageWarehouses() && !$user->isAdminOfWarehouse($warehouse->id)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Bạn không có quyền cập nhật kho này'], 403);
            }
            return redirect()->back()->with('error', 'Bạn không có quyền cập nhật kho này');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string',
            'province' => 'nullable|string|max:255', // Tên tỉnh/thành phố
            'province_code' => 'nullable|string|max:10', // Mã tỉnh/thành phố
            'ward' => 'nullable|string|max:255', // Tên phường/xã
            'ward_code' => 'nullable|string|max:20', // Mã phường/xã
            'phone' => 'nullable|string|max:20',
            'manager_name' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        $warehouse->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Kho đã được cập nhật',
                'data' => $warehouse->fresh(),
            ]);
        }

        return redirect()->route('admin.warehouses.show', $warehouse->id)->with('success', 'Kho đã được cập nhật');
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
        
        // Lấy thông tin kho gửi (tự động từ order nếu có)
        $fromWarehouse = null;
        if ($validated['from_warehouse_id'] ?? null) {
            $fromWarehouse = Warehouse::find($validated['from_warehouse_id']);
        } elseif ($order->warehouse_id) {
            // Nếu không chỉ định from_warehouse_id nhưng order có warehouse_id, dùng warehouse_id
            $fromWarehouse = Warehouse::find($order->warehouse_id);
        }

        // Tạo ghi chú
        $notes = $validated['notes'] ?? '';
        if ($fromWarehouse) {
            $notes = ($notes ? $notes . ' - ' : '') . "Nhận từ {$fromWarehouse->name} ({$fromWarehouse->province})";
        } elseif (!$notes) {
            $notes = 'Nhận từ kho khác';
        }

        // Lưu lại tài xế vận chuyển tỉnh (nếu có) trước khi cập nhật
        $intercityDriverId = $order->delivery_driver_id;
        
        $order->update([
            'warehouse_id' => $warehouseId,
            'status' => 'in_warehouse',
            'to_warehouse_id' => null, // Xóa to_warehouse_id khi đã nhận vào kho
            // Giữ nguyên delivery_driver_id để lưu lịch sử tài xế vận chuyển tỉnh
            // Sau này kho đích sẽ phân công tài xế shipper mới (có thể ghi đè delivery_driver_id)
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
        $statusNotes = $notes;
        if ($intercityDriverId) {
            $intercityDriver = \App\Models\Driver::find($intercityDriverId);
            if ($intercityDriver) {
                $statusNotes .= " - Tài xế vận chuyển: {$intercityDriver->name}";
            }
        }
        
        \App\Models\OrderStatus::create([
            'order_id' => $order->id,
            'status' => 'in_warehouse',
            'notes' => $statusNotes . " - Kho đích đã nhận được hàng. Có thể phân công tài xế shipper để giao hàng cho khách hàng.",
            'warehouse_id' => $warehouseId,
            'driver_id' => $intercityDriverId, // Lưu tài xế vận chuyển tỉnh vào lịch sử
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

        // Xuất cho shipper giao hàng (không set to_warehouse_id)
        $order->update([
            'status' => 'out_for_delivery',
            'to_warehouse_id' => null, // Đảm bảo không có to_warehouse_id
        ]);

        // Create order status
        \App\Models\OrderStatus::create([
            'order_id' => $order->id,
            'status' => 'out_for_delivery',
            'notes' => $validated['notes'] ?? 'Đã xuất kho cho shipper giao hàng',
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
     * Ship order to another warehouse (vận chuyển giữa các kho)
     */
    public function shipToWarehouse(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'order_ids' => 'required',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'intercity_driver_id' => 'nullable|exists:drivers,id',
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

        // Kiểm tra tài xế vận chuyển tỉnh (nếu có)
        if ($validated['intercity_driver_id'] ?? null) {
            $driver = \App\Models\Driver::find($validated['intercity_driver_id']);
            if ($driver && !$driver->isIntercityDriver()) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Tài xế được chọn không phải là tài xế vận chuyển tỉnh'], 400);
                }
                return redirect()->back()->with('error', 'Tài xế được chọn không phải là tài xế vận chuyển tỉnh');
            }
        }

        $fromWarehouseId = $validated['warehouse_id'] ?? Warehouse::getDefaultWarehouse()->id ?? null;
        $toWarehouse = Warehouse::findOrFail($validated['to_warehouse_id']);

        $orders = Order::whereIn('id', $orderIds)
            ->where('status', 'in_warehouse')
            ->where('warehouse_id', $fromWarehouseId)
            ->get();

        if ($orders->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Không có đơn hàng nào hợp lệ để vận chuyển'], 400);
            }
            return redirect()->back()->with('error', 'Không có đơn hàng nào hợp lệ để vận chuyển');
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($orders as $order) {
            try {
                // Cập nhật đơn hàng: status = in_transit, to_warehouse_id = kho đích
                $order->update([
                    'status' => 'in_transit',
                    'to_warehouse_id' => $validated['to_warehouse_id'],
                ]);

                // Tạo order status
                $notes = $validated['notes'] ?? "Đã xuất kho đi {$toWarehouse->name} ({$toWarehouse->province})";
                if ($validated['intercity_driver_id'] ?? null) {
                    $driver = \App\Models\Driver::find($validated['intercity_driver_id']);
                    $notes .= " - Tài xế: {$driver->name}";
                }

                \App\Models\OrderStatus::create([
                    'order_id' => $order->id,
                    'status' => 'in_transit',
                    'notes' => $notes,
                    'warehouse_id' => $fromWarehouseId,
                    'driver_id' => $validated['intercity_driver_id'] ?? null,
                    'updated_by' => auth()->id(),
                ]);

                // Tạo warehouse transaction (xuất kho)
                WarehouseTransaction::create([
                    'warehouse_id' => $fromWarehouseId,
                    'order_id' => $order->id,
                    'type' => 'out',
                    'reference_number' => $validated['reference_number'] ?? null,
                    'notes' => "Xuất kho vận chuyển đến {$toWarehouse->name}",
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
                'message' => "Đã xuất {$successCount} đơn hàng đi {$toWarehouse->name}" . ($failedCount > 0 ? ", {$failedCount} đơn thất bại" : ''),
                'data' => ['success' => $successCount, 'failed' => $failedCount],
            ]);
        }

        return redirect()->back()->with('success', "Đã xuất {$successCount} đơn hàng đi {$toWarehouse->name}" . ($failedCount > 0 ? ", {$failedCount} đơn thất bại" : ''));
    }

    /**
     * Bulk release orders from warehouse (xuất cho shipper giao hàng)
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

                // Xuất cho shipper giao hàng (không set to_warehouse_id)
                $order->update([
                    'status' => 'out_for_delivery',
                    'to_warehouse_id' => null, // Đảm bảo không có to_warehouse_id
                ]);

                // Create order status
                \App\Models\OrderStatus::create([
                    'order_id' => $order->id,
                    'status' => 'out_for_delivery',
                    'notes' => $validated['notes'] ?? 'Đã xuất kho cho shipper giao hàng',
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
