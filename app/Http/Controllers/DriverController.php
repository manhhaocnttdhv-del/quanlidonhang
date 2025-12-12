<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Driver::with('warehouse');

        if ($request->has('area')) {
            $query->where('area', $request->area);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $drivers = $query->orderBy('created_at', 'desc')->paginate(20);

        if ($request->expectsJson()) {
            return response()->json($drivers);
        }
        
        return view('admin.drivers.index', compact('drivers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'license_number' => 'nullable|string|max:50',
            'vehicle_type' => 'nullable|string|max:255',
            'vehicle_number' => 'nullable|string|max:50',
            'area' => 'nullable|string|max:255',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'notes' => 'nullable|string',
        ]);

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
