<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_orders' => Order::count(),
            'delivered' => Order::where('status', 'delivered')->count(),
            'processing' => Order::whereIn('status', ['pending', 'pickup_pending', 'picking_up', 'in_transit', 'out_for_delivery'])->count(),
            'revenue' => Order::whereDate('created_at', today())->sum('shipping_fee'),
        ];
        
        $recentOrders = Order::orderBy('created_at', 'desc')->limit(10)->get();
        
        return view('admin.dashboard', compact('stats', 'recentOrders'));
    }
}
