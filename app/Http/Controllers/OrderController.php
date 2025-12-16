<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\ShippingFee;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Order::with(['customer', 'pickupDriver', 'deliveryDriver', 'route', 'warehouse']);

        // Warehouse admin chỉ xem đơn hàng liên quan đến kho của mình
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $query->where(function($q) use ($user) {
                // Đơn hàng trong kho của mình
                $q->where('warehouse_id', $user->warehouse_id)
                  // Đơn hàng sẽ được chuyển đến kho của mình (to_warehouse_id) - BẤT KỂ STATUS NÀO
                  // QUAN TRỌNG: Ưu tiên to_warehouse_id, nếu đã set thì chỉ hiển thị ở kho đó
                  ->orWhere('to_warehouse_id', $user->warehouse_id)
                  // Đơn hàng đã giao trong khu vực kho này (chỉ khi không có to_warehouse_id hoặc to_warehouse_id trỏ đến kho này)
                  ->orWhere(function($subQ) use ($user) {
                      $subQ->where('status', 'delivered')
                           ->where(function($subSubQ) use ($user) {
                               // Chỉ hiển thị nếu to_warehouse_id là NULL hoặc trỏ đến kho này
                               $subSubQ->whereNull('to_warehouse_id')
                                      ->orWhere('to_warehouse_id', $user->warehouse_id);
                           })
                           ->where('receiver_province', $user->warehouse->province ?? '');
                  })
                  // Đơn hàng đang được tài xế của kho mình lấy (pickup_pending, picking_up, picked_up)
                  ->orWhere(function($subQ) use ($user) {
                      $subQ->whereIn('status', ['pickup_pending', 'picking_up', 'picked_up'])
                           ->whereHas('pickupDriver', function($driverQuery) use ($user) {
                               $driverQuery->where('warehouse_id', $user->warehouse_id);
                           });
                  })
                  // Đơn hàng có receiver_province trùng với tỉnh của kho mình NHƯNG CHỈ KHI to_warehouse_id là NULL
                  // (để hiển thị đơn hàng chưa chọn kho đích - chỉ áp dụng cho đơn hàng cũ chưa có to_warehouse_id)
                  ->orWhere(function($subQ) use ($user) {
                      if ($user->warehouse && $user->warehouse->province) {
                          $subQ->where('receiver_province', $user->warehouse->province)
                               ->whereNull('to_warehouse_id') // CHỈ khi to_warehouse_id là NULL
                               ->whereIn('status', ['pending', 'pickup_pending', 'picking_up', 'picked_up', 'in_transit']);
                      }
                  });
            });
        }

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('tracking_number')) {
            $query->where('tracking_number', 'like', '%' . $request->tracking_number . '%');
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        if ($request->expectsJson()) {
            return response()->json($orders);
        }

        return view('admin.orders.index', compact('orders'));
    }
    
    public function create()
    {
        $customers = \App\Models\Customer::where('is_active', true)->orderBy('name')->get();
        
        // Lấy kho của user (nếu là warehouse admin) hoặc kho mặc định
        $user = auth()->user();
        $warehouse = null;
        
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $warehouse = \App\Models\Warehouse::find($user->warehouse_id);
        }
        
        if (!$warehouse) {
            $warehouse = \App\Models\Warehouse::getDefaultWarehouse();
        }
        
        return view('admin.orders.create', compact('customers', 'warehouse'));
    }
    
    public function edit($id)
    {
        $order = Order::with(['warehouse', 'toWarehouse'])->findOrFail($id);
        return view('admin.orders.edit', compact('order'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_address' => 'required|string',
            'sender_province' => 'nullable|string|max:255',
            'sender_district' => 'nullable|string|max:255',
            'sender_ward' => 'nullable|string|max:255',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string',
            'receiver_province' => 'nullable|string|max:255',
            'receiver_district' => 'nullable|string|max:255',
            'receiver_ward' => 'nullable|string|max:255',
            'item_type' => 'nullable|string|max:255',
            'weight' => 'required|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'cod_amount' => 'nullable|numeric|min:0',
            'service_type' => 'nullable|in:express,standard,economy',
            'is_fragile' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'pickup_method' => 'required|in:driver,warehouse', // driver: tài xế đến lấy, warehouse: đưa đến kho
            'to_warehouse_id' => 'nullable|exists:warehouses,id', // Kho vận chuyển đến
        ]);

        // Generate tracking number
        $trackingNumber = $this->generateTrackingNumber();

        // Xác định kho tạo đơn hàng
        // Nếu user là warehouse admin → dùng kho của user
        // Nếu không → dùng kho mặc định (Nghệ An)
        $user = auth()->user();
        $originWarehouse = null;
        
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            // User là warehouse admin → dùng kho của user
            $originWarehouse = \App\Models\Warehouse::find($user->warehouse_id);
        }
        
        // Nếu không có kho của user, dùng kho mặc định
        if (!$originWarehouse) {
            $originWarehouse = \App\Models\Warehouse::getDefaultWarehouse();
        }

        // Set sender province từ kho tạo đơn hàng
        $validated['sender_province'] = $originWarehouse->province ?? 'Hà Nội';
        $validated['sender_district'] = $originWarehouse->district ?? '';
        $validated['sender_ward'] = $originWarehouse->ward ?? '';
        
        // Tính phí vận chuyển ước tính và lưu vào database
        // Phí vận chuyển này có thể được cập nhật lại khi giao hàng thành công
        // Đảm bảo dùng đúng province của kho gửi
        $fromProvince = $originWarehouse ? $originWarehouse->province : 'Hà Nội';
        if (!$fromProvince) {
            // Nếu kho không có province, dùng province từ validated hoặc mặc định
            $fromProvince = $validated['sender_province'] ?? 'Hà Nội';
        }
        
        $shippingFee = $this->calculateShippingFee(
            $fromProvince,
            $validated['sender_district'] ?? '',
            $validated['receiver_province'] ?? '',
            $validated['receiver_district'] ?? '',
            $validated['weight'],
            $validated['service_type'] ?? 'standard',
            $validated['cod_amount'] ?? 0
        );
        
        // Xác định trạng thái ban đầu dựa trên phương thức nhận hàng
        $pickupMethod = $validated['pickup_method'] ?? 'driver';
        $initialStatus = $pickupMethod === 'warehouse' ? 'in_warehouse' : 'pending';
        
        // Xóa pickup_method khỏi validated (không lưu vào database)
        unset($validated['pickup_method']);
        
        // Xóa shipping_fee khỏi validated (nếu có) - sẽ dùng giá trị tính toán
        unset($validated['shipping_fee']);

        // Tạo đơn hàng - Lưu phí vận chuyển ước tính
        $orderData = array_merge($validated, [
            'tracking_number' => $trackingNumber,
            'shipping_fee' => $shippingFee, // Lưu phí vận chuyển ước tính khi tạo đơn
            'status' => $initialStatus,
            'warehouse_id' => $originWarehouse->id ?? null,
            'to_warehouse_id' => $validated['to_warehouse_id'] ?? null,
            'created_by' => auth()->id(),
        ]);
        
        $order = Order::create($orderData);

        // Create initial status
        if ($pickupMethod === 'warehouse') {
            // Nếu đưa đến kho, tạo status in_warehouse và warehouse transaction
            $warehouseName = $originWarehouse ? $originWarehouse->name : 'Nghệ An';
            OrderStatus::create([
                'order_id' => $order->id,
                'status' => 'in_warehouse',
                'notes' => "Người gửi đã đưa hàng đến kho {$warehouseName}",
                'warehouse_id' => $originWarehouse->id ?? null,
                'updated_by' => auth()->id(),
            ]);
            
            // Tạo warehouse transaction
            \App\Models\WarehouseTransaction::create([
                'warehouse_id' => $originWarehouse->id,
                'order_id' => $order->id,
                'type' => 'in',
                'transaction_date' => now(),
                'notes' => "Người gửi đưa hàng đến kho {$warehouseName}",
                'created_by' => auth()->id(),
            ]);
        } else {
            // Nếu tài xế đến lấy, tạo status pending
            OrderStatus::create([
                'order_id' => $order->id,
                'status' => 'pending',
                'notes' => 'Đơn hàng mới được tạo, chờ tài xế đến lấy',
                'warehouse_id' => $originWarehouse->id ?? null,
                'updated_by' => auth()->id(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đơn hàng đã được tạo thành công',
                'data' => $order->load(['customer', 'statuses']),
            ], 201);
        }

        return redirect()->route('admin.orders.show', $order->id)->with('success', 'Đơn hàng đã được tạo thành công');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $order = Order::with([
            'customer',
            'pickupDriver',
            'deliveryDriver',
            'route',
            'warehouse',
            'toWarehouse',
            'statuses' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'warehouseTransactions',
            'complaints',
        ])->findOrFail($id);

        if ($request->expectsJson()) {
            return response()->json($order);
        }

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'sender_name' => 'sometimes|string|max:255',
            'sender_phone' => 'sometimes|string|max:20',
            'sender_address' => 'sometimes|string',
            'receiver_name' => 'sometimes|string|max:255',
            'receiver_phone' => 'sometimes|string|max:20',
            'receiver_address' => 'sometimes|string',
            'item_type' => 'nullable|string|max:255',
            'weight' => 'sometimes|numeric|min:0',
            'cod_amount' => 'nullable|numeric|min:0',
            'service_type' => 'nullable|in:express,standard,economy',
            'is_fragile' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);
        
        // Convert checkbox value to boolean
        if (isset($validated['is_fragile'])) {
            $validated['is_fragile'] = (bool) $validated['is_fragile'];
        }

        $order->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đơn hàng đã được cập nhật',
                'data' => $order->fresh(),
            ]);
        }

        return redirect()->route('admin.orders.show', $order->id)->with('success', 'Đơn hàng đã được cập nhật');
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string',
            'notes' => 'nullable|string',
            'location' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'driver_id' => 'nullable|exists:drivers,id',
        ]);

        $order->update([
            'status' => $validated['status'],
            'warehouse_id' => $validated['warehouse_id'] ?? $order->warehouse_id,
        ]);

        if ($validated['status'] === 'picked_up') {
            $order->update(['picked_up_at' => now()]);
        }

        if ($validated['status'] === 'delivered') {
            $order->update(['delivered_at' => now()]);
        }

        OrderStatus::create([
            'order_id' => $order->id,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'location' => $validated['location'] ?? null,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'driver_id' => $validated['driver_id'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Trạng thái đơn hàng đã được cập nhật',
                'data' => $order->fresh(['statuses']),
            ]);
        }

        return redirect()->back()->with('success', 'Trạng thái đơn hàng đã được cập nhật');
    }

    /**
     * Generate unique tracking number
     */
    private function generateTrackingNumber(): string
    {
        do {
            $trackingNumber = 'VD' . date('Ymd') . strtoupper(Str::random(6));
        } while (Order::where('tracking_number', $trackingNumber)->exists());

        return $trackingNumber;
    }

    /**
     * Calculate shipping fee - Sử dụng cùng logic với ShippingFeeController::calculate()
     */
    private function calculateShippingFee(
        string $fromProvince,
        string $fromDistrict,
        string $toProvince,
        string $toDistrict,
        float $weight,
        string $serviceType,
        float $codAmount
    ): float {
        // Priority 1: Find exact match (province + district)
        $shippingFee = ShippingFee::where('from_province', $fromProvince)
            ->where('from_district', $fromDistrict ?? '')
            ->where('to_province', $toProvince)
            ->where('to_district', $toDistrict ?? '')
            ->where('service_type', $serviceType)
            ->where('is_active', true)
            ->first();

        // Priority 2: Find by province only (same province or different province)
        if (!$shippingFee) {
            $shippingFee = ShippingFee::where('from_province', $fromProvince)
                ->where(function($query) {
                    $query->whereNull('from_district')
                          ->orWhere('from_district', '');
                })
                ->where('to_province', $toProvince)
                ->where(function($query) {
                    $query->whereNull('to_district')
                          ->orWhere('to_district', '');
                })
                ->where('service_type', $serviceType)
                ->where('is_active', true)
                ->first();
        }

        // Priority 3: Find by region (same region = same price)
        if (!$shippingFee) {
            $fromRegion = $this->getRegion($fromProvince);
            $toRegion = $this->getRegion($toProvince);
            
            if ($fromRegion === $toRegion) {
                // Same region - use regional pricing
                $shippingFee = $this->getRegionalFee($fromRegion, $serviceType);
            } else {
                // Different regions - use inter-regional pricing
                $shippingFee = $this->getInterRegionalFee($fromRegion, $toRegion, $serviceType);
            }
        }

        // Priority 4: Default fee based on service type (với tính toán weight và COD)
        if (!$shippingFee) {
            // Tạo default fee structure với tính toán đầy đủ
            $defaultFees = [
                'express' => ['base_fee' => 50000, 'weight_fee_per_kg' => 10000, 'cod_fee_percent' => 2],
                'standard' => ['base_fee' => 30000, 'weight_fee_per_kg' => 8000, 'cod_fee_percent' => 1.5],
                'economy' => ['base_fee' => 20000, 'weight_fee_per_kg' => 6000, 'cod_fee_percent' => 1.5],
            ];
            
            $defaultFee = $defaultFees[$serviceType] ?? ['base_fee' => 30000, 'weight_fee_per_kg' => 8000, 'cod_fee_percent' => 1.5];
            
            // Tính toán phí đầy đủ: base + weight + COD
            $baseFee = $defaultFee['base_fee'];
            $minWeight = 0.5;
            $weightFee = max(0, $weight - $minWeight) * $defaultFee['weight_fee_per_kg'];
            $codFee = $codAmount * ($defaultFee['cod_fee_percent'] / 100);
            
            return $baseFee + $weightFee + $codFee;
        }

        // Handle both object (from DB) and stdClass (from regional fee) access
        if (is_object($shippingFee)) {
            $baseFee = $shippingFee->base_fee ?? 0;
            $weightFeePerKg = $shippingFee->weight_fee_per_kg ?? 0;
            $codFeePercent = $shippingFee->cod_fee_percent ?? 0;
            $minWeight = $shippingFee->min_weight ?? 0.5;
        } else {
            $baseFee = $shippingFee['base_fee'] ?? 0;
            $weightFeePerKg = $shippingFee['weight_fee_per_kg'] ?? 0;
            $codFeePercent = $shippingFee['cod_fee_percent'] ?? 0;
            $minWeight = $shippingFee['min_weight'] ?? 0.5;
        }
        
        // Tính toán phí đầy đủ: base + weight + COD
        $weightFee = max(0, $weight - $minWeight) * $weightFeePerKg;
        $codFee = $codAmount * ($codFeePercent / 100);
        
        return $baseFee + $weightFee + $codFee;
    }

    /**
     * Get region of province
     */
    private function getRegion(string $province): string
    {
        // Normalize province name (remove "Thành phố", "Tỉnh" prefix)
        $normalizedProvince = trim(preg_replace('/^(Thành phố|Tỉnh)\s+/', '', $province));
        
        $northernProvinces = [
            'Hà Nội', 'Hải Phòng', 'Hà Giang', 'Cao Bằng', 'Bắc Kạn', 'Tuyên Quang', 
            'Lào Cai', 'Điện Biên', 'Lai Châu', 'Sơn La', 'Yên Bái', 'Hòa Bình', 
            'Thái Nguyên', 'Lạng Sơn', 'Quảng Ninh', 'Bắc Giang', 'Phú Thọ', 
            'Vĩnh Phúc', 'Bắc Ninh', 'Hải Dương', 'Hưng Yên', 'Thái Bình', 
            'Hà Nam', 'Nam Định', 'Ninh Bình'
        ];
        
        $centralProvinces = [
            'Thanh Hóa', 'Nghệ An', 'Hà Tĩnh', 'Quảng Bình', 'Quảng Trị', 
            'Thừa Thiên Huế', 'Đà Nẵng', 'Quảng Nam', 'Quảng Ngãi', 'Bình Định', 
            'Phú Yên', 'Khánh Hòa', 'Ninh Thuận', 'Bình Thuận', 'Kon Tum', 
            'Gia Lai', 'Đắk Lắk', 'Đắk Nông', 'Lâm Đồng'
        ];
        
        // Check normalized name first
        if (in_array($normalizedProvince, $northernProvinces, true)) {
            return 'north';
        } elseif (in_array($normalizedProvince, $centralProvinces, true)) {
            return 'central';
        }
        
        // Also check original name
        if (in_array($province, $northernProvinces, true)) {
            return 'north';
        } elseif (in_array($province, $centralProvinces, true)) {
            return 'central';
        }
        
        // Default to south if not found
        return 'south';
    }

    /**
     * Get regional shipping fee (same region)
     */
    private function getRegionalFee(string $region, string $serviceType)
    {
        $regionalFees = [
            'north' => [
                'express' => ['base_fee' => 35000, 'weight_fee_per_kg' => 7000, 'cod_fee_percent' => 1.5],
                'standard' => ['base_fee' => 25000, 'weight_fee_per_kg' => 5000, 'cod_fee_percent' => 1.5],
                'economy' => ['base_fee' => 18000, 'weight_fee_per_kg' => 3000, 'cod_fee_percent' => 1.5],
            ],
            'central' => [
                'express' => ['base_fee' => 40000, 'weight_fee_per_kg' => 8000, 'cod_fee_percent' => 1.8],
                'standard' => ['base_fee' => 28000, 'weight_fee_per_kg' => 6000, 'cod_fee_percent' => 1.8],
                'economy' => ['base_fee' => 20000, 'weight_fee_per_kg' => 4000, 'cod_fee_percent' => 1.8],
            ],
            'south' => [
                'express' => ['base_fee' => 35000, 'weight_fee_per_kg' => 7000, 'cod_fee_percent' => 1.5],
                'standard' => ['base_fee' => 25000, 'weight_fee_per_kg' => 5000, 'cod_fee_percent' => 1.5],
                'economy' => ['base_fee' => 18000, 'weight_fee_per_kg' => 3000, 'cod_fee_percent' => 1.5],
            ],
        ];

        $fee = $regionalFees[$region][$serviceType] ?? null;
        if (!$fee) return null;

        // Create a temporary object-like structure
        return (object) [
            'base_fee' => $fee['base_fee'],
            'weight_fee_per_kg' => $fee['weight_fee_per_kg'],
            'cod_fee_percent' => $fee['cod_fee_percent'],
            'min_weight' => 0.5,
            'max_weight' => 50,
        ];
    }

    /**
     * Get inter-regional shipping fee (different regions)
     */
    private function getInterRegionalFee(string $fromRegion, string $toRegion, string $serviceType)
    {
        // Same region already handled
        if ($fromRegion === $toRegion) {
            return $this->getRegionalFee($fromRegion, $serviceType);
        }

        // Adjacent regions (North-Central or Central-South)
        $isAdjacent = 
            ($fromRegion === 'north' && $toRegion === 'central') ||
            ($fromRegion === 'central' && $toRegion === 'north') ||
            ($fromRegion === 'central' && $toRegion === 'south') ||
            ($fromRegion === 'south' && $toRegion === 'central');

        // Far regions (North-South)
        $isFar = 
            ($fromRegion === 'north' && $toRegion === 'south') ||
            ($fromRegion === 'south' && $toRegion === 'north');

        if ($isAdjacent) {
            $fees = [
                'express' => ['base_fee' => 60000, 'weight_fee_per_kg' => 12000, 'cod_fee_percent' => 2],
                'standard' => ['base_fee' => 45000, 'weight_fee_per_kg' => 9000, 'cod_fee_percent' => 2],
                'economy' => ['base_fee' => 35000, 'weight_fee_per_kg' => 7000, 'cod_fee_percent' => 2],
            ];
        } elseif ($isFar) {
            $fees = [
                'express' => ['base_fee' => 80000, 'weight_fee_per_kg' => 15000, 'cod_fee_percent' => 2.5],
                'standard' => ['base_fee' => 60000, 'weight_fee_per_kg' => 12000, 'cod_fee_percent' => 2.5],
                'economy' => ['base_fee' => 45000, 'weight_fee_per_kg' => 9000, 'cod_fee_percent' => 2.5],
            ];
        } else {
            return null;
        }

        $fee = $fees[$serviceType] ?? null;
        if (!$fee) return null;

        return (object) [
            'base_fee' => $fee['base_fee'],
            'weight_fee_per_kg' => $fee['weight_fee_per_kg'],
            'cod_fee_percent' => $fee['cod_fee_percent'],
            'min_weight' => 0.5,
            'max_weight' => 50,
        ];
    }
}
