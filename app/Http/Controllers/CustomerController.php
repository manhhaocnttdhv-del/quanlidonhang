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
        $query = Customer::with('user');

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

        $customers = $query->orderBy('created_at', 'desc')->paginate(20);

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
        $customer = Customer::findOrFail($id);
        return view('admin.customers.edit', compact('customer'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
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
        ]);

        $code = $this->generateCustomerCode();

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

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'province' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'ward' => 'nullable|string|max:255',
            'tax_code' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

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
