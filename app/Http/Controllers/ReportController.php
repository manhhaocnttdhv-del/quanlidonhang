<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Warehouse;
use App\Models\WarehouseTransaction;
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
            'total_revenue' => Order::whereDate('created_at', today())->sum('shipping_fee'), // Chỉ tính phí vận chuyển, không bao gồm COD
        ];
        
        $reportData = Order::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total_orders, 
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered_orders,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_orders,
                SUM(shipping_fee) as total_revenue,
                SUM(cod_amount) as cod_amount,
                SUM(shipping_fee) + SUM(cod_amount) as total_revenue_with_cod')
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

    /**
     * Get comprehensive report for all warehouses (for super admin)
     */
    public function warehousesOverview(Request $request)
    {
        $dateFrom = $request->get('date_from', date('Y-m-d', strtotime('-30 days')));
        $dateTo = $request->get('date_to', date('Y-m-d'));

        $warehouses = Warehouse::where('is_active', true)->get();

        $warehouseStats = [];
        foreach ($warehouses as $warehouse) {
            // Đơn hàng trong kho hiện tại
            $currentInventory = Order::where('warehouse_id', $warehouse->id)
                ->where('status', 'in_warehouse')
                ->count();

            // Đơn hàng đang đến kho
            $incomingOrders = Order::where('to_warehouse_id', $warehouse->id)
                ->where('status', 'in_transit')
                ->count();

            // Thống kê theo ngày (trong khoảng thời gian)
            $statsInPeriod = Order::where('warehouse_id', $warehouse->id)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered_orders,
                    SUM(CASE WHEN status = "in_warehouse" THEN 1 ELSE 0 END) as in_warehouse_orders,
                    SUM(CASE WHEN status = "in_transit" THEN 1 ELSE 0 END) as in_transit_orders,
                    SUM(shipping_fee) as total_shipping_revenue,
                    SUM(cod_amount) as total_cod_amount
                ')
                ->first();

            // Nhập kho (theo transactions)
            $inTransactions = WarehouseTransaction::where('warehouse_id', $warehouse->id)
                ->where('type', 'in')
                ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                ->count();

            // Xuất kho (theo transactions)
            $outTransactions = WarehouseTransaction::where('warehouse_id', $warehouse->id)
                ->where('type', 'out')
                ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                ->count();

            // Đơn hàng nhận từ kho khác
            $receivedFromOtherWarehouses = WarehouseTransaction::where('warehouse_id', $warehouse->id)
                ->where('type', 'in')
                ->where('notes', 'like', '%Nhận từ%kho%')
                ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                ->count();

            // Đơn hàng xuất đi kho khác
            $shippedToOtherWarehouses = Order::where('warehouse_id', $warehouse->id)
                ->whereNotNull('to_warehouse_id')
                ->where('status', 'in_transit')
                ->whereBetween('updated_at', [$dateFrom, $dateTo])
                ->count();

            // Thống kê tài xế của kho
            $driversCount = \App\Models\Driver::where('warehouse_id', $warehouse->id)
                ->where('is_active', true)
                ->count();
            
            $shippersCount = \App\Models\Driver::where('warehouse_id', $warehouse->id)
                ->where('driver_type', 'shipper')
                ->where('is_active', true)
                ->count();
            
            $intercityDriversCount = \App\Models\Driver::where('warehouse_id', $warehouse->id)
                ->where('driver_type', 'intercity_driver')
                ->where('is_active', true)
                ->count();

            // Admin kho
            $warehouseAdmins = \App\Models\User::where('warehouse_id', $warehouse->id)
                ->where('role', 'warehouse_admin')
                ->where('is_active', true)
                ->get(['name', 'email', 'phone']);

            $warehouseStats[] = [
                'warehouse' => $warehouse,
                'current_inventory' => $currentInventory,
                'incoming_orders' => $incomingOrders,
                'total_orders' => $statsInPeriod->total_orders ?? 0,
                'delivered_orders' => $statsInPeriod->delivered_orders ?? 0,
                'in_warehouse_orders' => $statsInPeriod->in_warehouse_orders ?? 0,
                'in_transit_orders' => $statsInPeriod->in_transit_orders ?? 0,
                'total_shipping_revenue' => $statsInPeriod->total_shipping_revenue ?? 0,
                'total_cod_amount' => $statsInPeriod->total_cod_amount ?? 0,
                'in_transactions' => $inTransactions,
                'out_transactions' => $outTransactions,
                'received_from_other_warehouses' => $receivedFromOtherWarehouses,
                'shipped_to_other_warehouses' => $shippedToOtherWarehouses,
                'drivers_count' => $driversCount,
                'shippers_count' => $shippersCount,
                'intercity_drivers_count' => $intercityDriversCount,
                'warehouse_admins' => $warehouseAdmins,
            ];
        }

        // Tổng hợp tất cả kho
        $totalStats = [
            'total_warehouses' => $warehouses->count(),
            'total_current_inventory' => array_sum(array_column($warehouseStats, 'current_inventory')),
            'total_incoming_orders' => array_sum(array_column($warehouseStats, 'incoming_orders')),
            'total_orders' => array_sum(array_column($warehouseStats, 'total_orders')),
            'total_delivered' => array_sum(array_column($warehouseStats, 'delivered_orders')),
            'total_shipping_revenue' => array_sum(array_column($warehouseStats, 'total_shipping_revenue')),
            'total_cod_amount' => array_sum(array_column($warehouseStats, 'total_cod_amount')),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'summary' => $totalStats,
                'warehouses' => $warehouseStats,
            ]);
        }

        return view('admin.reports.warehouses-overview', compact('warehouseStats', 'totalStats', 'dateFrom', 'dateTo'));
    }
}
