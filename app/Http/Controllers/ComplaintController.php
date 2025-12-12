<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ComplaintController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Complaint::with(['order', 'customer', 'assignedTo', 'resolvedBy']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        $complaints = $query->orderBy('created_at', 'desc')->paginate(20);

        if ($request->expectsJson()) {
            return response()->json($complaints);
        }
        
        return view('admin.complaints.index', compact('complaints'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'customer_id' => 'nullable|exists:customers,id',
            'type' => 'required|in:delay,lost,wrong_cod,damaged,other',
            'description' => 'required|string',
        ]);

        $ticketNumber = $this->generateTicketNumber();

        $complaint = Complaint::create([
            ...$validated,
            'ticket_number' => $ticketNumber,
            'status' => 'open',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Khiếu nại đã được tạo',
                'data' => $complaint->load(['order', 'customer']),
            ], 201);
        }
        
        return redirect()->route('admin.complaints.index')->with('success', 'Khiếu nại đã được tạo');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $complaint = Complaint::with(['order', 'customer', 'assignedTo', 'resolvedBy'])->findOrFail($id);
        
        if ($request->expectsJson()) {
            return response()->json($complaint);
        }
        
        return view('admin.complaints.show', compact('complaint'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $complaint = Complaint::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:open,in_progress,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
            'resolution' => 'nullable|string',
            'compensation_amount' => 'nullable|numeric|min:0',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'resolved') {
            $validated['resolved_at'] = now();
            $validated['resolved_by'] = auth()->id();
        }

        $complaint->update($validated);

        return response()->json([
            'message' => 'Khiếu nại đã được cập nhật',
            'data' => $complaint->fresh(),
        ]);
    }

    /**
     * Resolve complaint
     */
    public function resolve(Request $request, string $id)
    {
        $complaint = Complaint::findOrFail($id);

        $validated = $request->validate([
            'resolution' => 'required|string',
            'compensation_amount' => 'nullable|numeric|min:0',
        ]);

        $complaint->update([
            'status' => 'resolved',
            'resolution' => $validated['resolution'],
            'compensation_amount' => $validated['compensation_amount'] ?? 0,
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Khiếu nại đã được xử lý',
                'data' => $complaint->fresh(),
            ]);
        }
        
        return redirect()->back()->with('success', 'Khiếu nại đã được xử lý');
    }

    /**
     * Generate unique ticket number
     */
    private function generateTicketNumber(): string
    {
        do {
            $ticketNumber = 'KN' . date('Ymd') . strtoupper(Str::random(6));
        } while (Complaint::where('ticket_number', $ticketNumber)->exists());

        return $ticketNumber;
    }
}
