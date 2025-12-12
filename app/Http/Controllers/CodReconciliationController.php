<?php

namespace App\Http\Controllers;

use App\Models\CodReconciliation;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CodReconciliationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CodReconciliation::with(['customer', 'createdBy']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $reconciliations = $query->orderBy('created_at', 'desc')->paginate(20);
        
        $customers = \App\Models\Customer::where('is_active', true)->get();

        if ($request->expectsJson()) {
            return response()->json($reconciliations);
        }
        
        return view('admin.cod-reconciliations.index', compact('reconciliations', 'customers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id',
        ]);

        $orders = Order::whereIn('id', $validated['order_ids'])
            ->where('status', 'delivered')
            ->whereBetween('delivered_at', [$validated['from_date'], $validated['to_date']])
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'message' => 'Không có đơn hàng nào phù hợp',
            ], 400);
        }

        $totalCodAmount = $orders->sum('cod_amount');
        $totalShippingFee = $orders->sum('shipping_fee');
        $totalAmount = $totalCodAmount + $totalShippingFee;

        $reconciliationNumber = $this->generateReconciliationNumber();

        $reconciliation = CodReconciliation::create([
            'reconciliation_number' => $reconciliationNumber,
            'customer_id' => $validated['customer_id'] ?? null,
            'from_date' => $validated['from_date'],
            'to_date' => $validated['to_date'],
            'total_cod_amount' => $totalCodAmount,
            'total_shipping_fee' => $totalShippingFee,
            'total_amount' => $totalAmount,
            'remaining_amount' => $totalAmount,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        // Attach orders
        foreach ($orders as $order) {
            $reconciliation->orders()->attach($order->id, [
                'cod_amount' => $order->cod_amount,
                'shipping_fee' => $order->shipping_fee,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Bảng kê đã được tạo',
                'data' => $reconciliation->load(['orders', 'customer']),
            ], 201);
        }
        
        return redirect()->route('admin.cod-reconciliations.show', $reconciliation->id)->with('success', 'Bảng kê đã được tạo');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $reconciliation = CodReconciliation::with(['orders', 'customer', 'createdBy'])->findOrFail($id);
        
        if ($request->expectsJson()) {
            return response()->json($reconciliation);
        }
        
        return view('admin.cod-reconciliations.show', compact('reconciliation'));
    }

    /**
     * Update payment status
     */
    public function updatePayment(Request $request, string $id)
    {
        $reconciliation = CodReconciliation::findOrFail($id);

        $validated = $request->validate([
            'paid_amount' => 'required|numeric|min:0',
        ]);

        $paidAmount = $validated['paid_amount'];
        $remainingAmount = $reconciliation->total_amount - $paidAmount;

        $status = 'partial';
        if ($remainingAmount <= 0) {
            $status = 'paid';
            $remainingAmount = 0;
        }

        $reconciliation->update([
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'status' => $status,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Thanh toán đã được cập nhật',
                'data' => $reconciliation->fresh(),
            ]);
        }
        
        return redirect()->back()->with('success', 'Thanh toán đã được cập nhật');
    }

    /**
     * Generate unique reconciliation number
     */
    private function generateReconciliationNumber(): string
    {
        do {
            $number = 'BK' . date('Ymd') . strtoupper(Str::random(6));
        } while (CodReconciliation::where('reconciliation_number', $number)->exists());

        return $number;
    }
}
