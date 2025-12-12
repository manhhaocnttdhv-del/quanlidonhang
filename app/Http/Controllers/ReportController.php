<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display reports index page
     */
    public function index(Request $request)
    {
        $dateFrom = $request->get('date_from', date('Y-m-d', strtotime('-30 days')));
        $dateTo = $request->get('date_to', date('Y-m-d'));
        
        $dailyStats = [
            'total_orders' => Order::whereDate('created_at', today())->count(),
            'delivered_orders' => Order::where('status', 'delivered')
                ->whereDate('delivered_at', today())
                ->count(),
            'failed_orders' => Order::where('status', 'failed')
                ->whereDate('updated_at', today())
                ->count(),
            'total_revenue' => Order::whereDate('created_at', today())->sum('shipping_fee'),
        ];
        
        $reportData = Order::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total_orders, 
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered_orders,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_orders,
                SUM(shipping_fee) as total_revenue,
                SUM(cod_amount) as cod_amount')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
        
        return view('admin.reports.index', compact('dailyStats', 'reportData'));
    }
    
    /**
     * Get daily report
     */
    public function daily(Request $request)
    {
        $date = $request->get('date', today()->format('Y-m-d'));

        $stats = [
            'date' => $date,
            'total_orders' => Order::whereDate('created_at', $date)->count(),
            'delivered_orders' => Order::where('status', 'delivered')
                ->whereDate('delivered_at', $date)
                ->count(),
            'failed_orders' => Order::where('status', 'failed')
                ->whereDate('updated_at', $date)
                ->count(),
            'returned_orders' => Order::where('status', 'returned')
                ->whereDate('updated_at', $date)
                ->count(),
            'total_revenue' => Order::whereDate('created_at', $date)
                ->sum('shipping_fee'),
            'total_cod' => Order::whereDate('created_at', $date)
                ->sum('cod_amount'),
            'cod_collected' => Order::where('status', 'delivered')
                ->whereDate('delivered_at', $date)
                ->sum('cod_amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Get monthly report
     */
    public function monthly(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));

        $stats = [
            'month' => $month,
            'total_orders' => Order::whereYear('created_at', substr($month, 0, 4))
                ->whereMonth('created_at', substr($month, 5, 2))
                ->count(),
            'delivered_orders' => Order::where('status', 'delivered')
                ->whereYear('delivered_at', substr($month, 0, 4))
                ->whereMonth('delivered_at', substr($month, 5, 2))
                ->count(),
            'failed_orders' => Order::where('status', 'failed')
                ->whereYear('updated_at', substr($month, 0, 4))
                ->whereMonth('updated_at', substr($month, 5, 2))
                ->count(),
            'total_revenue' => Order::whereYear('created_at', substr($month, 0, 4))
                ->whereMonth('created_at', substr($month, 5, 2))
                ->sum('shipping_fee'),
            'total_cod' => Order::whereYear('created_at', substr($month, 0, 4))
                ->whereMonth('created_at', substr($month, 5, 2))
                ->sum('cod_amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Get driver performance report
     */
    public function driverPerformance(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $drivers = Driver::withCount([
            'deliveryOrders as delivered_count' => function ($query) use ($dateFrom, $dateTo) {
                $query->where('status', 'delivered')
                    ->whereBetween('delivered_at', [$dateFrom, $dateTo]);
            },
            'deliveryOrders as failed_count' => function ($query) use ($dateFrom, $dateTo) {
                $query->where('status', 'failed')
                    ->whereBetween('updated_at', [$dateFrom, $dateTo]);
            },
        ])->get();

        return response()->json($drivers);
    }

    /**
     * Get warehouse report
     */
    public function warehouse(Request $request)
    {
        $warehouseId = $request->get('warehouse_id');
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $query = Order::whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $stats = [
            'total_in' => $query->where('status', 'in_warehouse')->count(),
            'total_out' => $query->where('status', 'in_transit')->count(),
            'current_inventory' => Order::where('status', 'in_warehouse')
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get revenue report
     */
    public function revenue(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $revenue = Order::whereBetween('created_at', [$dateFrom, $dateTo])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(shipping_fee) as shipping_revenue'),
                DB::raw('SUM(cod_amount) as cod_amount'),
                DB::raw('COUNT(*) as order_count')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($revenue);
    }
}
