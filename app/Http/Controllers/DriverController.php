<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Driver::with('warehouse');

        // Warehouse admin chỉ xem tài xế của kho mình
        if ($user->isWarehouseAdmin()) {
            $query->where('warehouse_id', $user->warehouse_id);
        }

        if ($request->has('area')) {
            $query->where('area', $request->area);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('driver_type')) {
            $query->where('driver_type', $request->driver_type);
        }

        $drivers = $query->orderBy('created_at', 'desc')->paginate(20);

        if ($request->expectsJson()) {
            return response()->json($drivers);
        }
        
        // Super admin và admin xem tất cả kho, warehouse admin chỉ xem kho của mình
        if ($user->canManageWarehouses()) {
            $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        } else {
            $warehouses = Warehouse::where('id', $user->warehouse_id)
                ->where('is_active', true)
                ->get();
        }
        
        return view('admin.drivers.index', compact('drivers', 'warehouses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'license_number' => 'nullable|string|max:50',
            'vehicle_type' => 'nullable|string|max:255',
            'vehicle_number' => 'nullable|string|max:50',
            'area' => 'nullable|string|max:255',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'driver_type' => 'required|in:shipper,intercity_driver',
            'notes' => 'nullable|string',
        ]);

        // Nếu là warehouse admin, tự động gán kho của họ
        if ($user->isWarehouseAdmin()) {
            $validated['warehouse_id'] = $user->warehouse_id;
        }

        $code = $this->generateDriverCode();

        $driver = Driver::create([
            ...$validated,
            'code' => $code,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tài xế đã được tạo',
                'data' => $driver,
            ], 201);
        }
        
        return redirect()->route('admin.drivers.index')->with('success', 'Tài xế đã được tạo');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $driver = Driver::with(['warehouse', 'pickupOrders', 'deliveryOrders'])->findOrFail($id);
        return response()->json($driver);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $driver = Driver::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:255',
            'license_number' => 'nullable|string|max:50',
            'vehicle_type' => 'nullable|string|max:255',
            'vehicle_number' => 'nullable|string|max:50',
            'area' => 'nullable|string|max:255',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'driver_type' => 'sometimes|in:shipper,intercity_driver',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        $driver->update($validated);

        return response()->json([
            'message' => 'Tài xế đã được cập nhật',
            'data' => $driver->fresh(),
        ]);
    }

    /**
     * Generate unique driver code
     */
    private function generateDriverCode(): string
    {
        do {
            $code = 'TX' . strtoupper(Str::random(8));
        } while (Driver::where('code', $code)->exists());

        return $code;
    }
}
