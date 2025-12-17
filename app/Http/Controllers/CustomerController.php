<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Customer::with(['user', 'warehouse']);

        // Warehouse admin chỉ xem khách hàng của kho mình
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $query->where('warehouse_id', $user->warehouse_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $customers = $query->withCount('orders')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        if ($request->expectsJson()) {
            return response()->json($customers);
        }

        return view('admin.customers.index', compact('customers'));
    }
    
    public function create()
    {
        return view('admin.customers.create');
    }
    
    public function edit($id)
    {
        $customer = Customer::with('warehouse')->findOrFail($id);
        
        $user = auth()->user();
        
        // Warehouse admin chỉ sửa được khách hàng của kho mình
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            if ($customer->warehouse_id != $user->warehouse_id) {
                return redirect()->route('admin.customers.index')->with('error', 'Bạn không có quyền sửa khách hàng này');
            }
        }
        
        // Super admin và admin xem tất cả kho, warehouse admin chỉ xem kho của mình
        $warehouses = collect();
        if ($user->canManageWarehouses()) {
            $warehouses = \App\Models\Warehouse::where('is_active', true)->orderBy('name')->get();
        } else {
            $warehouses = \App\Models\Warehouse::where('id', $user->warehouse_id)
                ->where('is_active', true)
                ->get();
        }
        
        return view('admin.customers.edit', compact('customer', 'warehouses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'province' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'ward' => 'nullable|string|max:255',
            'tax_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $code = $this->generateCustomerCode();

        // Nếu là warehouse admin, tự động gán warehouse_id và province
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $validated['warehouse_id'] = $user->warehouse_id;
            // Tự động set province từ warehouse nếu chưa có
            if (empty($validated['province']) && $user->warehouse) {
                $validated['province'] = $user->warehouse->province;
                $validated['district'] = $validated['district'] ?? $user->warehouse->district;
                $validated['ward'] = $validated['ward'] ?? $user->warehouse->ward;
            }
        }

        $customer = Customer::create([
            ...$validated,
            'code' => $code,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Khách hàng đã được tạo',
                'data' => $customer,
            ], 201);
        }

        return redirect()->route('admin.customers.index')->with('success', 'Khách hàng đã được tạo thành công');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $customer = Customer::with(['orders', 'complaints'])->findOrFail($id);
        
        if ($request->expectsJson()) {
            return response()->json($customer);
        }
        
        return view('admin.customers.show', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $customer = Customer::findOrFail($id);

        $user = auth()->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'province' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'ward' => 'nullable|string|max:255',
            'tax_code' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        // Nếu là warehouse admin, không cho phép đổi warehouse_id
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $validated['warehouse_id'] = $user->warehouse_id;
        }

        $customer->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Khách hàng đã được cập nhật',
                'data' => $customer->fresh(),
            ]);
        }

        return redirect()->route('admin.customers.index')->with('success', 'Khách hàng đã được cập nhật');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $customer = Customer::findOrFail($id);
        
        $user = auth()->user();
        
        // Warehouse admin chỉ xóa được khách hàng của kho mình
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            if ($customer->warehouse_id != $user->warehouse_id) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'message' => 'Bạn không có quyền xóa khách hàng này',
                    ], 403);
                }
                return redirect()->back()->with('error', 'Bạn không có quyền xóa khách hàng này');
            }
        }
        
        // Kiểm tra xem khách hàng có đơn hàng không
        if ($customer->orders()->count() > 0) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Không thể xóa khách hàng vì đã có đơn hàng liên quan',
                ], 400);
            }
            return redirect()->back()->with('error', 'Không thể xóa khách hàng vì đã có đơn hàng liên quan');
        }
        
        $customer->delete();
        
        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Khách hàng đã được xóa thành công',
            ]);
        }
        
        return redirect()->route('admin.customers.index')->with('success', 'Khách hàng đã được xóa thành công');
    }

    /**
     * Generate unique customer code
     */
    private function generateCustomerCode(): string
    {
        do {
            $code = 'KH' . strtoupper(Str::random(8));
        } while (Customer::where('code', $code)->exists());

        return $code;
    }
}
