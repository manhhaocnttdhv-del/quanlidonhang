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
        
        $user = auth()->user();
        $warehouseFilter = null;
        if ($user && $user->isWarehouseAdmin() && $user->warehouse_id) {
            $warehouseFilter = $user->warehouse_id;
        }
        
        // Lấy tất cả đơn hàng: có warehouse_id = kho này HOẶC có transaction 'out' từ kho này
        $dailyStatsQuery = Order::whereDate('created_at', today());
        if ($warehouseFilter) {
            $dailyStatsQuery->where(function($q) use ($warehouseFilter) {
                $q->where('warehouse_id', $warehouseFilter)
                  ->orWhereHas('warehouseTransactions', function($transQ) use ($warehouseFilter) {
                      $transQ->where('warehouse_id', $warehouseFilter)
                            ->where('type', 'out');
                  });
            });
        }
        
        // Tính doanh thu dựa trên kho gửi (từ transaction 'out' đầu tiên) hoặc warehouse_id hiện tại
        // Lấy tất cả đơn hàng đã giao trong ngày
        $deliveredOrdersQuery = Order::where('status', 'delivered')
            ->whereDate('delivered_at', today());
        
        // Lọc theo kho gửi: tìm transaction 'out' đầu tiên của mỗi đơn hàng
        if ($warehouseFilter) {
            $deliveredOrdersQuery->where(function($q) use ($warehouseFilter) {
                // Đơn hàng có warehouse_id = kho này
                $q->where('warehouse_id', $warehouseFilter)
                  // HOẶC có transaction 'out' từ kho này (kho gửi)
                  ->orWhereHas('warehouseTransactions', function($transQ) use ($warehouseFilter) {
                      $transQ->where('warehouse_id', $warehouseFilter)
                            ->where('type', 'out');
                  });
            });
        }
        
        $deliveredOrders = $deliveredOrdersQuery->get();
        
        // Lọc lại để chỉ lấy đơn hàng có kho gửi = kho filter
        if ($warehouseFilter) {
            $deliveredOrders = $deliveredOrders->filter(function($order) use ($warehouseFilter) {
                // Nếu warehouse_id = kho filter, tính cho kho này
                if ($order->warehouse_id == $warehouseFilter) {
                    return true;
                }
                // Nếu có transaction 'out' từ kho filter, tính cho kho này
                $firstOutTransaction = WarehouseTransaction::where('order_id', $order->id)
                    ->where('type', 'out')
                    ->orderBy('transaction_date', 'asc')
                    ->first();
                return $firstOutTransaction && $firstOutTransaction->warehouse_id == $warehouseFilter;
            });
        }
        
        // Lấy tất cả đơn hàng để đếm total_orders (bao gồm cả đơn hàng xuất kho)
        $allDailyOrders = $dailyStatsQuery->get();
        if ($warehouseFilter) {
            $allDailyOrders = $allDailyOrders->filter(function($order) use ($warehouseFilter) {
                if ($order->warehouse_id == $warehouseFilter) {
                    return true;
                }
                $firstOutTransaction = WarehouseTransaction::where('order_id', $order->id)
                    ->where('type', 'out')
                    ->orderBy('transaction_date', 'asc')
                    ->first();
                return $firstOutTransaction && $firstOutTransaction->warehouse_id == $warehouseFilter;
            });
        }
        
        // Lấy đơn hàng thất bại (bao gồm cả đơn hàng xuất kho)
        $failedOrdersQuery = Order::where('status', 'failed')
            ->whereDate('updated_at', today());
        if ($warehouseFilter) {
            $failedOrdersQuery->where(function($q) use ($warehouseFilter) {
                $q->where('warehouse_id', $warehouseFilter)
                  ->orWhereHas('warehouseTransactions', function($transQ) use ($warehouseFilter) {
                      $transQ->where('warehouse_id', $warehouseFilter)
                            ->where('type', 'out');
                  });
            });
        }
        $failedOrders = $failedOrdersQuery->get();
        if ($warehouseFilter) {
            $failedOrders = $failedOrders->filter(function($order) use ($warehouseFilter) {
                if ($order->warehouse_id == $warehouseFilter) {
                    return true;
                }
                $firstOutTransaction = WarehouseTransaction::where('order_id', $order->id)
                    ->where('type', 'out')
                    ->orderBy('transaction_date', 'asc')
                    ->first();
                return $firstOutTransaction && $firstOutTransaction->warehouse_id == $warehouseFilter;
            });
        }
        
        $dailyStats = [
            'total_orders' => $allDailyOrders->count(),
            'delivered_orders' => $deliveredOrders->count(),
            'failed_orders' => $failedOrders->count(),
            // Doanh thu = COD đã thu (cod_collected) + Phí vận chuyển (chỉ tính đơn hàng đã giao thành công)
            'total_revenue' => $deliveredOrders->sum(function($order) {
                return ($order->cod_collected ?? $order->cod_amount ?? 0) + ($order->shipping_fee ?? 0);
            }),
        ];
        
        // Lấy tất cả đơn hàng trong khoảng thời gian: có warehouse_id = kho này HOẶC có transaction 'out' từ kho này
        $reportDataQuery = Order::whereBetween('created_at', [$dateFrom, $dateTo]);
        if ($warehouseFilter) {
            $reportDataQuery->where(function($q) use ($warehouseFilter) {
                $q->where('warehouse_id', $warehouseFilter)
                  ->orWhereHas('warehouseTransactions', function($transQ) use ($warehouseFilter) {
                      $transQ->where('warehouse_id', $warehouseFilter)
                            ->where('type', 'out');
                  });
            });
        }
        
        // Tính báo cáo dựa trên kho gửi (từ transaction 'out' đầu tiên) hoặc warehouse_id hiện tại
        // Lấy tất cả đơn hàng trong khoảng thời gian
        $allOrders = $reportDataQuery->get();
        
        // Lọc lại để chỉ lấy đơn hàng có kho gửi = kho filter (nếu có)
        if ($warehouseFilter) {
            $allOrders = $allOrders->filter(function($order) use ($warehouseFilter) {
                // Nếu warehouse_id = kho filter, tính cho kho này
                if ($order->warehouse_id == $warehouseFilter) {
                    return true;
                }
                // Nếu có transaction 'out' từ kho filter, tính cho kho này
                $firstOutTransaction = WarehouseTransaction::where('order_id', $order->id)
                    ->where('type', 'out')
                    ->orderBy('transaction_date', 'asc')
                    ->first();
                return $firstOutTransaction && $firstOutTransaction->warehouse_id == $warehouseFilter;
            });
        }
        
        // Nhóm theo ngày và tính toán
        $reportData = $allOrders->groupBy(function($order) {
            return $order->created_at->format('Y-m-d');
        })->map(function($orders, $date) {
            return [
                'date' => $date,
                'total_orders' => $orders->count(),
                'delivered_orders' => $orders->where('status', 'delivered')->count(),
                'failed_orders' => $orders->where('status', 'failed')->count(),
                'total_revenue' => $orders->where('status', 'delivered')->sum(function($order) {
                    return ($order->cod_collected ?? $order->cod_amount ?? 0) + ($order->shipping_fee ?? 0);
                }),
                'cod_collected' => $orders->where('status', 'delivered')->sum(function($order) {
                    return $order->cod_collected ?? $order->cod_amount ?? 0;
                }),
                'shipping_fee' => $orders->where('status', 'delivered')->sum('shipping_fee'),
                'cod_amount' => $orders->sum('cod_amount'),
            ];
        })->values()->sortByDesc('date');
        
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
