<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /**
     * Display tracking index page
     */
    public function index()
    {
        return view('admin.tracking.index');
    }
    
    /**
     * Track order by tracking number
     */
    public function track(Request $request)
    {
        $validated = $request->validate([
            'tracking_number' => 'required|string',
        ]);

        $order = Order::where('tracking_number', $validated['tracking_number'])
            ->with([
                'customer',
                'pickupDriver',
                'deliveryDriver',
                'route',
                'warehouse',
                'statuses' => function ($query) {
                    $query->orderBy('created_at', 'asc')
                        ->with(['warehouse', 'driver', 'updatedBy']);
                },
            ])
            ->first();

        if (!$order) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Không tìm thấy đơn hàng với mã vận đơn này',
                ], 404);
            }
            return response()->json([
                'message' => 'Không tìm thấy đơn hàng với mã vận đơn này',
            ], 404);
        }

        return response()->json([
            'order' => $order,
            'current_status' => $order->status,
            'status_history' => $order->statuses,
        ]);
    }

    /**
     * Get order status by tracking number (public API)
     */
    public function getStatus(string $trackingNumber)
    {
        $order = Order::where('tracking_number', $trackingNumber)
            ->with(['statuses' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Không tìm thấy đơn hàng',
            ], 404);
        }

        return response()->json([
            'tracking_number' => $order->tracking_number,
            'status' => $order->status,
            'status_text' => $this->getStatusText($order->status),
            'receiver_name' => $order->receiver_name,
            'receiver_address' => $order->receiver_address,
            'status_history' => $order->statuses->map(function ($status) {
                return [
                    'status' => $status->status,
                    'status_text' => $this->getStatusText($status->status),
                    'notes' => $status->notes,
                    'location' => $status->location,
                    'updated_at' => $status->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }

    /**
     * Get status text in Vietnamese
     */
    private function getStatusText(string $status): string
    {
        $statusMap = [
            'pending' => 'Chờ xử lý',
            'pickup_pending' => 'Chờ lấy hàng',
            'picking_up' => 'Đang lấy hàng',
            'picked_up' => 'Đã lấy hàng',
            'in_warehouse' => 'Đã nhập kho',
            'in_transit' => 'Đang vận chuyển',
            'out_for_delivery' => 'Đang giao hàng',
            'delivered' => 'Đã giao hàng',
            'failed' => 'Giao hàng thất bại',
            'returned' => 'Đã hoàn',
        ];

        return $statusMap[$status] ?? $status;
    }
}
