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
        $query = Order::with(['customer', 'pickupDriver', 'deliveryDriver', 'route', 'warehouse']);

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
        return view('admin.orders.create', compact('customers'));
    }
    
    public function edit($id)
    {
        $order = Order::findOrFail($id);
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
        ]);

        // Generate tracking number
        $trackingNumber = $this->generateTrackingNumber();

        // Calculate shipping fee (always from Nghệ An)
        $shippingFee = $this->calculateShippingFee(
            'Nghệ An', // Always from Nghệ An
            $validated['sender_district'] ?? '',
            $validated['receiver_province'] ?? '',
            $validated['receiver_district'] ?? '',
            $validated['weight'],
            $validated['service_type'] ?? 'standard',
            $validated['cod_amount'] ?? 0
        );

        // Get default warehouse (Nghệ An)
        $defaultWarehouse = \App\Models\Warehouse::getDefaultWarehouse();

        // Force sender province to Nghệ An
        $validated['sender_province'] = 'Nghệ An';

        $order = Order::create([
            ...$validated,
            'tracking_number' => $trackingNumber,
            'shipping_fee' => $shippingFee,
            'status' => 'pending',
            'warehouse_id' => $defaultWarehouse->id ?? null,
            'created_by' => auth()->id(),
        ]);

        // Create initial status
        OrderStatus::create([
            'order_id' => $order->id,
            'status' => 'pending',
            'notes' => 'Đơn hàng mới được tạo',
            'updated_by' => auth()->id(),
        ]);

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
            'notes' => 'nullable|string',
        ]);

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
     * Calculate shipping fee
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
        $shippingFee = ShippingFee::where('from_province', $fromProvince)
            ->where('from_district', $fromDistrict)
            ->where('to_province', $toProvince)
            ->where('to_district', $toDistrict)
            ->where('service_type', $serviceType)
            ->where('is_active', true)
            ->first();

        if (!$shippingFee) {
            // Default fee if no rule found
            return 30000; // 30,000 VND default
        }

        $baseFee = $shippingFee->base_fee;
        $weightFee = max(0, $weight - $shippingFee->min_weight) * $shippingFee->weight_fee_per_kg;
        $codFee = $codAmount * ($shippingFee->cod_fee_percent / 100);

        return $baseFee + $weightFee + $codFee;
    }
}
