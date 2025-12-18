<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Xây dựng query cơ bản
        $ordersQuery = Order::query();
        $recentOrdersQuery = Order::query();
        
        // Nếu là warehouse admin, chỉ xem đơn hàng liên quan đến kho của mình
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $ordersQuery->where(function($query) use ($user) {
                $query->where('warehouse_id', $user->warehouse_id)
                      ->orWhere('to_warehouse_id', $user->warehouse_id)
                      ->orWhere(function($q) use ($user) {
                          // Đơn hàng đã giao trong khu vực kho này
                          $q->where('status', 'delivered')
                            ->where('receiver_province', $user->warehouse->province ?? '');
                      });
            });
            
            $recentOrdersQuery->where(function($query) use ($user) {
                $query->where('warehouse_id', $user->warehouse_id)
                      ->orWhere('to_warehouse_id', $user->warehouse_id);
            });
        }
        
        $stats = [
            'total_orders' => (clone $ordersQuery)->count(),
            'delivered' => (clone $ordersQuery)->where('status', 'delivered')->count(),
            'processing' => (clone $ordersQuery)->whereIn('status', ['pending', 'pickup_pending', 'picking_up', 'in_transit', 'out_for_delivery'])->count(),
            'revenue' => (clone $ordersQuery)->whereDate('created_at', today())->sum('shipping_fee'),
        ];
        
        $recentOrders = $recentOrdersQuery->orderBy('created_at', 'desc')->limit(10)->get();
        if($user->isWarehouseAdmin() && $user->warehouse_id) {
            $warehouse = \App\Models\Warehouse::findOrFail($user->warehouse_id);
            return redirect()->route('admin.reports.index', $warehouse->id);
        }else{
            return view('admin.reports.warehouses-overview', compact('stats', 'recentOrders'));
        }
    }
}
