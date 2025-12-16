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
        
        // Đơn hàng đang đến kho (in_transit với to_warehouse_id = kho này HOẶC đang vận chuyển đến tỉnh của kho này)
        $ordersIncomingQuery = Order::where('status', 'in_transit')
            ->with(['customer', 'route', 'warehouse', 'pickupDriver', 'deliveryDriver']);
        
        // Hiển thị đơn hàng đang vận chuyển đến kho này HOẶC đến tỉnh của kho này
        if ($warehouse->province) {
            $ordersIncomingQuery->where(function($q) use ($id, $warehouse) {
                $q->where('to_warehouse_id', $id)
                  ->orWhere('receiver_province', $warehouse->province);
            });
        } else {
            $ordersIncomingQuery->where('to_warehouse_id', $id);
        }
        
        $ordersIncoming = $ordersIncomingQuery->orderBy('updated_at', 'desc')->get();
        
        // Tất cả đơn hàng trong kho (status = 'in_warehouse')
        $allOrdersQuery = Order::where('warehouse_id', $id)
            ->where('status', 'in_warehouse')
            ->with(['customer', 'route', 'pickupDriver']);
        
        // Filter theo tỉnh nếu có
        if ($request->has('province') && $request->province) {
            $allOrdersQuery->where('receiver_province', $request->province);
        }
        
        $allOrders = $allOrdersQuery->orderBy('picked_up_at', 'desc')->get();
        
        // Lấy danh sách các đơn hàng có giao dịch nhập kho từ kho khác
        $ordersReceivedFromWarehouses = \App\Models\WarehouseTransaction::where('warehouse_id', $id)
            ->where('type', 'in')
            ->where(function($q) {
                $q->where('notes', 'like', '%Nhận từ%')
                  ->orWhere('notes', 'like', '%Nhận từ kho%')
                  ->orWhere('notes', 'like', '%từ kho%');
            })
            ->pluck('order_id')
            ->toArray();
        
        // Đơn hàng từ kho khác tới - BAO GỒM cả đơn hàng đã phân công shipper (out_for_delivery) để có thể cập nhật giao hàng và tính doanh thu
        $ordersFromOtherWarehousesQuery = Order::where('warehouse_id', $id)
            ->whereIn('status', ['in_warehouse', 'out_for_delivery']) // Bao gồm cả đơn hàng đã phân công shipper
            ->whereIn('id', $ordersReceivedFromWarehouses) // Chỉ lấy đơn hàng đã được nhận từ kho khác
            ->with(['customer', 'route', 'pickupDriver', 'deliveryDriver']);
        
        // Filter theo tỉnh nếu có
        if ($request->has('province') && $request->province) {
            $ordersFromOtherWarehousesQuery->where('receiver_province', $request->province);
        }
        
        $ordersFromOtherWarehouses = $ordersFromOtherWarehousesQuery->orderByRaw('CASE WHEN status = "in_warehouse" THEN 0 ELSE 1 END')
            ->orderBy('updated_at', 'desc')
            ->get();
        
        // Phân loại đơn hàng trong kho (chỉ status = 'in_warehouse')
        $ordersFromPickup = []; // Đơn hàng tài xế lấy từ người gửi
        
        foreach ($allOrders as $order) {
            // Kiểm tra đơn hàng có được nhận từ kho khác không (qua WarehouseTransaction)
            if (!in_array($order->id, $ordersReceivedFromWarehouses)) {
                // Kiểm tra đơn hàng đã xuất kho chưa (có transaction 'out' từ kho hiện tại)
                $hasOutTransaction = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                    ->where('warehouse_id', $id)
                    ->where('type', 'out')
                    ->whereDate('transaction_date', '>=', now()->subDays(30))
                    ->exists();
                
                // CHỈ hiển thị đơn hàng:
                // 1. Có pickup_driver_id (từ tài xế lấy về)
                // 2. CHƯA xuất kho (không có transaction 'out' từ kho hiện tại)
                // 3. CHƯA được phân công tài xế giao hàng (không có delivery_driver_id) - vì nếu đã phân công thì đã xuất kho hoặc sắp xuất kho
                if ($order->pickup_driver_id && !$hasOutTransaction && !$order->delivery_driver_id) {
                    // Đơn hàng từ tài xế lấy về: có pickup_driver_id, chưa xuất kho, và chưa phân công tài xế giao hàng
                    $ordersFromPickup[] = $order;
                }
            }
        }
        
        // Filter theo tỉnh nếu có request (cho orders_from_pickup và orders_from_other_warehouses)
        $ordersFromPickupFiltered = collect($ordersFromPickup);
        $ordersFromOtherWarehousesFiltered = collect($ordersFromOtherWarehouses);
        
        if ($request->has('province') && $request->province) {
            $ordersFromPickupFiltered = $ordersFromPickupFiltered->filter(function($order) use ($request) {
                return $order->receiver_province == $request->province;
            });
            $ordersFromOtherWarehousesFiltered = $ordersFromOtherWarehousesFiltered->filter(function($order) use ($request) {
                return $order->receiver_province == $request->province;
            });
        }
        
        // Đơn hàng đang giao hàng trong khu vực của kho này (tỉnh nhận trùng với tỉnh của kho)
        $ordersBeingDelivered = Order::where('status', 'out_for_delivery')
            ->where('receiver_province', $warehouse->province)
            ->whereNotNull('delivery_driver_id')
            ->with(['customer', 'deliveryDriver', 'warehouse'])
            ->orderBy('delivery_scheduled_at', 'desc')
            ->get();
        
        $inventory = [
            'orders' => $allOrders,
            'orders_incoming' => $ordersIncoming, // Đơn hàng đang đến kho (chưa nhận)
            'orders_from_pickup' => $ordersFromPickupFiltered->values(),
            'orders_from_other_warehouses' => $ordersFromOtherWarehousesFiltered->values(),
            'orders_being_delivered' => $ordersBeingDelivered,
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
            // Hàng vừa xuất hôm nay (tất cả giao dịch xuất kho)
            'today_exported' => WarehouseTransaction::where('warehouse_id', $id)
                ->where('type', 'out')
                ->whereDate('transaction_date', today())
                ->with(['order.customer', 'route', 'createdBy'])
                ->orderBy('transaction_date', 'desc')
                ->get(),
            // Lịch sử xuất kho (7 ngày gần nhất, có thể filter theo request)
            'exported_history' => WarehouseTransaction::where('warehouse_id', $id)
                ->where('type', 'out')
                ->when($request->has('export_date_from'), function($q) use ($request) {
                    $q->whereDate('transaction_date', '>=', $request->export_date_from);
                }, function($q) {
                    // Mặc định 7 ngày gần nhất
                    $q->whereDate('transaction_date', '>=', now()->subDays(7));
                })
                ->when($request->has('export_date_to'), function($q) use ($request) {
                    $q->whereDate('transaction_date', '<=', $request->export_date_to);
                })
                ->with(['order.customer', 'route', 'createdBy'])
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

        // Warehouse_id: ưu tiên từ request, nếu không có thì lấy từ kho của user đăng nhập (nếu là warehouse admin)
        $user = auth()->user();
        $warehouseId = $validated['warehouse_id'] ?? null;
        
        if (!$warehouseId && $user->isWarehouseAdmin() && $user->warehouse_id) {
            $warehouseId = $user->warehouse_id;
        }

        if (!$warehouseId) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Vui lòng chỉ định kho nhận hàng'], 400);
            }
            return redirect()->back()->with('error', 'Vui lòng chỉ định kho nhận hàng');
        }

        // Kiểm tra kho nhận có tồn tại không
        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Kho nhận không tồn tại'], 400);
            }
            return redirect()->back()->with('error', 'Kho nhận không tồn tại');
        }
        
        // Lấy thông tin kho gửi
        $fromWarehouse = null;
        if (isset($validated['from_warehouse_id']) && $validated['from_warehouse_id']) {
            $fromWarehouse = Warehouse::find($validated['from_warehouse_id']);
        } elseif ($order->warehouse_id && $order->status === 'in_transit') {
            // Nếu đơn hàng đang ở trạng thái in_transit, warehouse_id hiện tại là kho gửi
            $fromWarehouse = Warehouse::find($order->warehouse_id);
        } elseif ($order->warehouse_id && $order->status !== 'in_transit') {
            // Nếu không phải in_transit, có thể warehouse_id là kho hiện tại, cần kiểm tra lịch sử
            // Tìm giao dịch xuất kho gần nhất từ một kho khác
            $lastOutTransaction = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                ->where('type', 'out')
                ->where('warehouse_id', '!=', $warehouseId)
                ->orderBy('transaction_date', 'desc')
                ->first();
            if ($lastOutTransaction) {
                $fromWarehouse = Warehouse::find($lastOutTransaction->warehouse_id);
            } else {
                // Fallback: dùng warehouse_id hiện tại
                $fromWarehouse = Warehouse::find($order->warehouse_id);
            }
        }

        // Tạo ghi chú
        $notes = $validated['notes'] ?? '';
        if ($fromWarehouse) {
            $notes = ($notes ? $notes . ' - ' : '') . "Nhận từ {$fromWarehouse->name}";
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
        
        // Tạo OrderStatus với thông tin rõ ràng về việc nhận từ kho nào
        $finalStatusNotes = $fromWarehouse 
            ? "Đơn hàng từ kho {$fromWarehouse->name} ({$fromWarehouse->province}) vào kho {$warehouse->name}"
            : "Đơn hàng từ kho khác vào kho {$warehouse->name}";
        
        if ($intercityDriverId) {
            $intercityDriver = \App\Models\Driver::find($intercityDriverId);
            if ($intercityDriver) {
                $finalStatusNotes .= " - Tài xế vận chuyển: {$intercityDriver->name}";
            }
        }
        
        \App\Models\OrderStatus::create([
            'order_id' => $order->id,
            'status' => 'in_warehouse',
            'notes' => $finalStatusNotes . " - Kho đích đã nhận được hàng. Có thể phân công tài xế shipper để giao hàng cho khách hàng.",
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
     * Bulk receive orders from other warehouses
     */
    public function bulkReceiveOrder(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id',
        ]);

        $user = auth()->user();
        $warehouseId = null;
        
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $warehouseId = $user->warehouse_id;
        }

        if (!$warehouseId) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Vui lòng chỉ định kho nhận hàng'], 400);
            }
            return redirect()->back()->with('error', 'Vui lòng chỉ định kho nhận hàng');
        }

        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Kho nhận không tồn tại'], 400);
            }
            return redirect()->back()->with('error', 'Kho nhận không tồn tại');
        }

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($validated['order_ids'] as $orderId) {
            try {
                $order = Order::findOrFail($orderId);
                
                // Lấy thông tin kho gửi
                $fromWarehouse = null;
                if ($order->warehouse_id && $order->status === 'in_transit') {
                    $fromWarehouse = Warehouse::find($order->warehouse_id);
                } elseif ($order->warehouse_id) {
                    // Tìm giao dịch xuất kho gần nhất từ một kho khác
                    $lastOutTransaction = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                        ->where('type', 'out')
                        ->where('warehouse_id', '!=', $warehouseId)
                        ->orderBy('transaction_date', 'desc')
                        ->first();
                    if ($lastOutTransaction) {
                        $fromWarehouse = Warehouse::find($lastOutTransaction->warehouse_id);
                    } else {
                        $fromWarehouse = Warehouse::find($order->warehouse_id);
                    }
                }

                // Tạo ghi chú
                $notes = '';
                if ($fromWarehouse) {
                    $notes = "Nhận từ {$fromWarehouse->name}";
                } else {
                    $notes = 'Nhận từ kho khác';
                }

                // Lưu lại tài xế vận chuyển tỉnh (nếu có) trước khi cập nhật
                $intercityDriverId = $order->delivery_driver_id;
                
                $order->update([
                    'warehouse_id' => $warehouseId,
                    'status' => 'in_warehouse',
                    'to_warehouse_id' => null,
                ]);

                WarehouseTransaction::create([
                    'warehouse_id' => $warehouseId,
                    'order_id' => $order->id,
                    'type' => 'in',
                    'notes' => $notes,
                    'transaction_date' => now(),
                    'created_by' => auth()->id(),
                ]);

                // Tạo OrderStatus
                $finalStatusNotes = $fromWarehouse 
                    ? "Đơn hàng từ kho {$fromWarehouse->name} ({$fromWarehouse->province}) vào kho {$warehouse->name}"
                    : "Đơn hàng từ kho khác vào kho {$warehouse->name}";
                
                if ($intercityDriverId) {
                    $intercityDriver = \App\Models\Driver::find($intercityDriverId);
                    if ($intercityDriver) {
                        $finalStatusNotes .= " - Tài xế vận chuyển: {$intercityDriver->name}";
                    }
                }
                
                \App\Models\OrderStatus::create([
                    'order_id' => $order->id,
                    'status' => 'in_warehouse',
                    'notes' => $finalStatusNotes . " - Kho đích đã nhận được hàng. Có thể phân công tài xế shipper để giao hàng cho khách hàng.",
                    'warehouse_id' => $warehouseId,
                    'driver_id' => $intercityDriverId,
                    'updated_by' => auth()->id(),
                ]);

                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Đơn hàng #{$orderId}: " . $e->getMessage();
            }
        }

        $message = "Đã nhận {$successCount} đơn hàng vào kho thành công.";
        if ($failedCount > 0) {
            $message .= " {$failedCount} đơn hàng thất bại.";
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'errors' => $errors,
            ]);
        }
        
        return redirect()->back()->with('success', $message);
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

        // Warehouse_id: ưu tiên từ request, nếu không có thì dùng warehouse_id của đơn hàng
        $warehouseId = $validated['warehouse_id'] ?? $order->warehouse_id;
        
        if (!$warehouseId) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Không xác định được kho xuất'], 400);
            }
            return redirect()->back()->with('error', 'Không xác định được kho xuất');
        }

        // Lấy thông tin kho để tạo ghi chú và tìm tuyến
        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Kho không tồn tại'], 400);
            }
            return redirect()->back()->with('error', 'Kho không tồn tại');
        }

        // Tự động tìm tuyến từ tỉnh của kho đến tỉnh nhận nếu chưa chọn
        $routeId = $validated['route_id'] ?? null;
        if (!$routeId && $warehouse->province && $order->receiver_province) {
            $route = \App\Models\Route::where('is_active', true)
                ->where('from_province', $warehouse->province)
                ->where('to_province', $order->receiver_province)
                ->first();
            if ($route) {
                $routeId = $route->id;
            }
        }

        if ($routeId) {
            $order->update(['route_id' => $routeId]);
        }

        // Tạo ghi chú với tên kho nếu chưa có
        $notes = $validated['notes'] ?? "Đã chuẩn bị xuất kho từ {$warehouse->name}";

        // Đảm bảo to_warehouse_id = null (xuất cho shipper giao hàng, không điều phối đến kho khác)
        $order->update([
            'to_warehouse_id' => null,
        ]);

        // Tạo WarehouseTransaction để ghi nhận đã chuẩn bị xuất kho
        // Đơn hàng vẫn ở trạng thái in_warehouse, chờ phân công tài xế ở trang "Giao hàng"
        // KHÔNG tạo OrderStatus ở đây vì trạng thái đơn hàng không thay đổi (vẫn là in_warehouse)
        // OrderStatus sẽ được tạo khi phân công tài xế (chuyển sang out_for_delivery)
        WarehouseTransaction::create([
            'warehouse_id' => $warehouseId,
            'order_id' => $validated['order_id'],
            'type' => 'out',
            'route_id' => $routeId,
            'reference_number' => $validated['reference_number'] ?? null,
            'notes' => $notes,
            'transaction_date' => now(),
            'created_by' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đơn hàng đã được xuất kho',
                'data' => $order->fresh(),
            ]);
        }
        
        return redirect()->route('admin.delivery.index')->with('success', 'Đơn hàng đã được xuất kho. Vui lòng phân công tài xế giao hàng.');
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

        // Lấy danh sách đơn hàng trước
        $orders = Order::whereIn('id', $orderIds)
            ->where('status', 'in_warehouse')
            ->get();

        if ($orders->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Không có đơn hàng nào hợp lệ để vận chuyển'], 400);
            }
            return redirect()->back()->with('error', 'Không có đơn hàng nào hợp lệ để vận chuyển');
        }

        // Warehouse_id nguồn: ưu tiên từ request, nếu không có thì lấy từ đơn hàng đầu tiên
        // Tất cả đơn hàng phải cùng một kho nguồn
        $firstOrder = $orders->first();
        $fromWarehouseId = $validated['warehouse_id'] ?? $firstOrder->warehouse_id;

        if (!$fromWarehouseId) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Không xác định được kho nguồn'], 400);
            }
            return redirect()->back()->with('error', 'Không xác định được kho nguồn');
        }

        // Kiểm tra kho đích
        $toWarehouse = Warehouse::findOrFail($validated['to_warehouse_id']);
        if ($fromWarehouseId == $toWarehouse->id) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Kho nguồn và kho đích không được trùng nhau'], 400);
            }
            return redirect()->back()->with('error', 'Kho nguồn và kho đích không được trùng nhau');
        }

        // Lấy thông tin kho nguồn
        $fromWarehouse = Warehouse::find($fromWarehouseId);
        if (!$fromWarehouse) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Kho nguồn không tồn tại'], 400);
            }
            return redirect()->back()->with('error', 'Kho nguồn không tồn tại');
        }

        // Kiểm tra tất cả đơn hàng phải cùng một kho nguồn
        $orders = $orders->filter(function($order) use ($fromWarehouseId) {
            return $order->warehouse_id == $fromWarehouseId;
        });

        if ($orders->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tất cả đơn hàng phải cùng một kho nguồn'], 400);
            }
            return redirect()->back()->with('error', 'Tất cả đơn hàng phải cùng một kho nguồn');
        }

        // Tạo ghi chú mặc định
        $defaultNotes = "Xuất kho từ {$fromWarehouse->name} đi {$toWarehouse->name}";
        if ($validated['intercity_driver_id'] ?? null) {
            $driver = \App\Models\Driver::find($validated['intercity_driver_id']);
            if ($driver) {
                $defaultNotes .= " - Tài xế: {$driver->name}";
            }
        }
        $notes = $validated['notes'] ?? $defaultNotes;

        $successCount = 0;
        $failedCount = 0;

        foreach ($orders as $order) {
            try {
                // Cập nhật đơn hàng: status = in_transit, to_warehouse_id = kho đích
                $order->update([
                    'status' => 'in_transit',
                    'to_warehouse_id' => $validated['to_warehouse_id'],
                ]);

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
                    'notes' => $notes,
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

        // Lấy danh sách đơn hàng trước
        $orders = Order::whereIn('id', $orderIds)
            ->where('status', 'in_warehouse')
            ->get();

        if ($orders->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Không có đơn hàng nào hợp lệ để xuất kho'], 400);
            }
            return redirect()->back()->with('error', 'Không có đơn hàng nào hợp lệ để xuất kho');
        }

        // Warehouse_id: ưu tiên từ request, nếu không có thì lấy từ đơn hàng đầu tiên
        // Tất cả đơn hàng phải cùng một kho
        $firstOrder = $orders->first();
        $warehouseId = $validated['warehouse_id'] ?? $firstOrder->warehouse_id;
        
        if (!$warehouseId) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Không xác định được kho xuất'], 400);
            }
            return redirect()->back()->with('error', 'Không xác định được kho xuất');
        }

        // Lấy thông tin kho
        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Kho không tồn tại'], 400);
            }
            return redirect()->back()->with('error', 'Kho không tồn tại');
        }

        // Kiểm tra tất cả đơn hàng phải cùng một kho
        $differentWarehouseOrders = $orders->filter(function($order) use ($warehouseId) {
            return $order->warehouse_id != $warehouseId;
        });

        if ($differentWarehouseOrders->isNotEmpty()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Các đơn hàng phải cùng một kho'], 400);
            }
            return redirect()->back()->with('error', 'Các đơn hàng phải cùng một kho');
        }

        // Tạo ghi chú với tên kho nếu chưa có notes từ request
        $defaultNotes = "Xuất kho hàng loạt từ {$warehouse->name}";
        $notes = $validated['notes'] ?? $defaultNotes;

        $successCount = 0;
        $failedCount = 0;
        $globalRouteId = $validated['route_id'] ?? null; // Tuyến chung nếu chọn

        foreach ($orders as $order) {
            try {
                // Tìm tuyến: ưu tiên tuyến chung, nếu không có thì tự động tìm từ tỉnh kho đến tỉnh nhận
                $routeId = $globalRouteId;
                if (!$routeId && $warehouse->province && $order->receiver_province) {
                    $route = \App\Models\Route::where('is_active', true)
                        ->where('from_province', $warehouse->province)
                        ->where('to_province', $order->receiver_province)
                        ->first();
                    if ($route) {
                        $routeId = $route->id;
                    }
                }

                if ($routeId) {
                    $order->update(['route_id' => $routeId]);
                }

                // Đảm bảo to_warehouse_id = null (xuất cho shipper giao hàng, không điều phối đến kho khác)
                $order->update([
                    'to_warehouse_id' => null,
                ]);

                // Tạo WarehouseTransaction để ghi nhận đã chuẩn bị xuất kho
                // Đơn hàng vẫn ở trạng thái in_warehouse, chờ phân công tài xế ở trang "Giao hàng"
                // KHÔNG tạo OrderStatus ở đây vì trạng thái đơn hàng không thay đổi (vẫn là in_warehouse)
                // OrderStatus sẽ được tạo khi phân công tài xế (chuyển sang out_for_delivery)
                WarehouseTransaction::create([
                    'warehouse_id' => $warehouseId,
                    'order_id' => $order->id,
                    'type' => 'out',
                    'route_id' => $routeId,
                    'reference_number' => $validated['reference_number'] ?? null,
                    'notes' => $notes,
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
        
        return redirect()->route('admin.delivery.index')->with('success', "Đã xuất kho {$successCount} đơn hàng" . ($failedCount > 0 ? ", {$failedCount} đơn thất bại" : '') . ". Vui lòng phân công tài xế giao hàng.");
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
