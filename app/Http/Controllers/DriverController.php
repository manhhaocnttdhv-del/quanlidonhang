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

        $drivers = $query->withCount(['pickupOrders', 'deliveryOrders'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

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
    public function show(Request $request, string $id)
    {
        $driver = Driver::with(['warehouse', 'pickupOrders', 'deliveryOrders'])->findOrFail($id);
        
        if ($request->expectsJson()) {
            return response()->json($driver);
        }
        
        return view('admin.drivers.show', compact('driver'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $driver = Driver::with('warehouse')->findOrFail($id);
        
        $user = auth()->user();
        
        // Warehouse admin chỉ sửa được tài xế của kho mình
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            if ($driver->warehouse_id != $user->warehouse_id) {
                return redirect()->route('admin.drivers.index')->with('error', 'Bạn không có quyền sửa tài xế này');
            }
        }
        
        // Super admin và admin xem tất cả kho, warehouse admin chỉ xem kho của mình
        if ($user->canManageWarehouses()) {
            $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        } else {
            $warehouses = Warehouse::where('id', $user->warehouse_id)
                ->where('is_active', true)
                ->get();
        }
        
        return view('admin.drivers.edit', compact('driver', 'warehouses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $driver = Driver::findOrFail($id);

        $user = auth()->user();
        
        // Warehouse admin chỉ sửa được tài xế của kho mình
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            if ($driver->warehouse_id != $user->warehouse_id) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Bạn không có quyền sửa tài xế này',
                    ], 403);
                }
                return redirect()->back()->with('error', 'Bạn không có quyền sửa tài xế này');
            }
        }
        
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
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        // Nếu là warehouse admin, không cho phép đổi warehouse_id
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $validated['warehouse_id'] = $user->warehouse_id;
        }

        $driver->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tài xế đã được cập nhật',
                'data' => $driver->fresh(),
            ]);
        }
        
        return redirect()->route('admin.drivers.index')->with('success', 'Tài xế đã được cập nhật thành công');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $driver = Driver::findOrFail($id);
        
        $user = auth()->user();
        
        // Warehouse admin chỉ xóa được tài xế của kho mình
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            if ($driver->warehouse_id != $user->warehouse_id) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'message' => 'Bạn không có quyền xóa tài xế này',
                    ], 403);
                }
                return redirect()->back()->with('error', 'Bạn không có quyền xóa tài xế này');
            }
        }
        
        // Kiểm tra xem tài xế có đơn hàng không
        $hasOrders = $driver->pickupOrders()->count() > 0 || $driver->deliveryOrders()->count() > 0;
        
        if ($hasOrders) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Không thể xóa tài xế vì đã có đơn hàng liên quan',
                ], 400);
            }
            return redirect()->back()->with('error', 'Không thể xóa tài xế vì đã có đơn hàng liên quan');
        }
        
        $driver->delete();
        
        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Tài xế đã được xóa thành công',
            ]);
        }
        
        return redirect()->route('admin.drivers.index')->with('success', 'Tài xế đã được xóa thành công');
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
