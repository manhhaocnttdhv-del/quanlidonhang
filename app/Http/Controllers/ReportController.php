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
        
        // Lấy đơn hàng thất bại và đã hủy (bao gồm cả đơn hàng xuất kho)
        $failedOrdersQuery = Order::whereIn('status', ['failed', 'cancelled'])
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
            // Doanh thu = COD đã thu (cod_collected) + Phí vận chuyển + Phí trả hàng (chỉ tính đơn hàng đã giao thành công)
            'total_revenue' => $deliveredOrders->sum(function($order) {
                $cod = $order->cod_collected ?? $order->cod_amount ?? 0;
                $shipping = $order->shipping_fee ?? 0;
                $returnFee = $order->return_fee ?? 0;
                return $cod + $shipping + $returnFee;
            }),
        ];
        
        $allOrdersQuery = Order::whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        
        $deliveredOrdersQuery = Order::where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        
        $failedOrdersQuery = Order::whereIn('status', ['failed', 'cancelled'])
            ->whereBetween('updated_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        
        if ($warehouseFilter) {
            $allOrdersQuery->where(function($q) use ($warehouseFilter) {
                $q->where('warehouse_id', $warehouseFilter)
                  ->orWhereHas('warehouseTransactions', function($transQ) use ($warehouseFilter) {
                      $transQ->where('warehouse_id', $warehouseFilter)->where('type', 'out');
                  });
            });
            
            $deliveredOrdersQuery->where(function($q) use ($warehouseFilter) {
                $q->where('warehouse_id', $warehouseFilter)
                  ->orWhereHas('warehouseTransactions', function($transQ) use ($warehouseFilter) {
                      $transQ->where('warehouse_id', $warehouseFilter)->where('type', 'out');
                  });
            });
            
            $failedOrdersQuery->where(function($q) use ($warehouseFilter) {
                $q->where('warehouse_id', $warehouseFilter)
                  ->orWhereHas('warehouseTransactions', function($transQ) use ($warehouseFilter) {
                      $transQ->where('warehouse_id', $warehouseFilter)->where('type', 'out');
                  });
            });
        }
        
        $allOrders = $allOrdersQuery->with('warehouseTransactions')->get();
        $deliveredOrders = $deliveredOrdersQuery->with('warehouseTransactions')->get();
        $failedOrders = $failedOrdersQuery->get();
        
        \Log::info('Report Data', [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'warehouse_filter' => $warehouseFilter,
            'all_orders_count' => $allOrders->count(),
            'delivered_orders_count' => $deliveredOrders->count(),
            'failed_orders_count' => $failedOrders->count(),
        ]);
        
        if ($warehouseFilter) {
            $allOrders = $allOrders->filter(function($order) use ($warehouseFilter) {
                if ($order->warehouse_id == $warehouseFilter) return true;
                $firstOut = $order->warehouseTransactions->where('type', 'out')->sortBy('transaction_date')->first();
                return $firstOut && $firstOut->warehouse_id == $warehouseFilter;
            });
            
            $deliveredOrders = $deliveredOrders->filter(function($order) use ($warehouseFilter) {
                if ($order->warehouse_id == $warehouseFilter) return true;
                $firstOut = $order->warehouseTransactions->where('type', 'out')->sortBy('transaction_date')->first();
                return $firstOut && $firstOut->warehouse_id == $warehouseFilter;
            });
            
            $failedOrders = $failedOrders->filter(function($order) use ($warehouseFilter) {
                if ($order->warehouse_id == $warehouseFilter) return true;
                $firstOut = WarehouseTransaction::where('order_id', $order->id)->where('type', 'out')->orderBy('transaction_date', 'asc')->first();
                return $firstOut && $firstOut->warehouse_id == $warehouseFilter;
            });
        }
        
        $reportData = collect();
        $dateFromTimestamp = strtotime($dateFrom);
        $dateToTimestamp = strtotime($dateTo);
        
        for ($date = $dateFromTimestamp; $date <= $dateToTimestamp; $date = strtotime('+1 day', $date)) {
            $dateStr = date('Y-m-d', $date);
            
            $ordersCreatedOnDate = $allOrders->filter(function($order) use ($dateStr) {
                if (!$order->created_at) return false;
                try {
                    $createdDate = is_string($order->created_at) ? date('Y-m-d', strtotime($order->created_at)) : $order->created_at->format('Y-m-d');
                    return $createdDate === $dateStr;
                } catch (\Exception $e) {
                    return false;
                }
            });
            
            $ordersDeliveredOnDate = $deliveredOrders->filter(function($order) use ($dateStr) {
                if (!$order->delivered_at) return false;
                try {
                    if (is_string($order->delivered_at)) {
                        $deliveredDate = date('Y-m-d', strtotime($order->delivered_at));
                    } else {
                        $deliveredDate = $order->delivered_at->format('Y-m-d');
                    }
                    return $deliveredDate === $dateStr;
                } catch (\Exception $e) {
                    return false;
                }
            });
            
            $ordersFailedOnDate = $failedOrders->filter(function($order) use ($dateStr) {
                if (!$order->updated_at) return false;
                $updatedDate = is_string($order->updated_at) ? date('Y-m-d', strtotime($order->updated_at)) : $order->updated_at->format('Y-m-d');
                return $updatedDate === $dateStr;
            });
            
            $allOrdersOnDate = $ordersCreatedOnDate->merge($ordersDeliveredOnDate)->merge($ordersFailedOnDate)->unique('id');
            
            $totalRevenue = $ordersDeliveredOnDate->sum(function($order) {
                $cod = $order->cod_collected ?? $order->cod_amount ?? 0;
                $shipping = $order->shipping_fee ?? 0;
                $returnFee = $order->return_fee ?? 0;
                return $cod + $shipping + $returnFee;
            });
            
            $codCollected = $ordersDeliveredOnDate->sum(function($order) {
                return $order->cod_collected ?? $order->cod_amount ?? 0;
            });
            
            $shippingFee = $ordersDeliveredOnDate->sum(function($order) {
                return $order->shipping_fee ?? 0;
            });
            
            $returnFee = $ordersDeliveredOnDate->sum(function($order) {
                return $order->return_fee ?? 0;
            });
            
            $codAmount = $allOrdersOnDate->sum(function($order) {
                return $order->cod_amount ?? 0;
            });
            
            $reportData->push([
                'date' => $dateStr,
                'total_orders' => $allOrdersOnDate->count(),
                'delivered_orders' => $ordersDeliveredOnDate->count(),
                'failed_orders' => $ordersFailedOnDate->count(),
                'total_revenue' => $totalRevenue,
                'cod_collected' => $codCollected,
                'shipping_fee' => $shippingFee,
                'return_fee' => $returnFee,
                'cod_amount' => $codAmount,
            ]);
        }
        
        $reportData = $reportData->filter(function($row) {
            return $row['total_orders'] > 0 || $row['delivered_orders'] > 0 || $row['failed_orders'] > 0;
        })->sortByDesc('date')->values();
        
        return view('admin.reports.index', compact('dailyStats', 'reportData', 'dateFrom', 'dateTo', 'warehouseFilter'));
    }
    
    public function detail(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $date = $request->get('date');
        $type = $request->get('type', 'delivered');
        
        if (!$dateFrom && !$dateTo && !$date) {
            return redirect()->route('admin.reports.index')->with('error', 'Vui lòng chọn ngày');
        }
        
        if ($date && !$dateFrom && !$dateTo) {
            $dateFrom = $date;
            $dateTo = $date;
        }
        
        if (!$dateFrom) {
            $dateFrom = $dateTo;
        }
        if (!$dateTo) {
            $dateTo = $dateFrom;
        }
        
        $user = auth()->user();
        $warehouseFilter = null;
        if ($user && $user->isWarehouseAdmin() && $user->warehouse_id) {
            $warehouseFilter = $user->warehouse_id;
        }
        
        if ($type === 'delivered') {
            $query = Order::where('status', 'delivered')
                ->whereNotNull('delivered_at')
                ->whereBetween('delivered_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        } elseif ($type === 'failed') {
            $query = Order::whereIn('status', ['failed', 'cancelled'])
                ->whereBetween('updated_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        } else {
            $query = Order::where(function($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                  ->orWhere(function($subQ) use ($dateFrom, $dateTo) {
                      $subQ->where('status', 'delivered')
                           ->whereNotNull('delivered_at')
                           ->whereBetween('delivered_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                  })
                  ->orWhere(function($subQ) use ($dateFrom, $dateTo) {
                      $subQ->whereIn('status', ['failed', 'cancelled'])
                           ->whereBetween('updated_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                  });
            });
        }
        
        if ($warehouseFilter) {
            $query->where(function($q) use ($warehouseFilter) {
                $q->where('warehouse_id', $warehouseFilter)
                  ->orWhereHas('warehouseTransactions', function($transQ) use ($warehouseFilter) {
                      $transQ->where('warehouse_id', $warehouseFilter)->where('type', 'out');
                  });
            });
        }
        
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%")
                  ->orWhere('sender_phone', 'like', "%{$search}%")
                  ->orWhere('receiver_name', 'like', "%{$search}%")
                  ->orWhere('receiver_phone', 'like', "%{$search}%")
                  ->orWhere('receiver_address', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('status') && $request->status) {
            if ($request->status === 'cancelled_delivered') {
                $query->where('status', 'delivered')
                      ->where(function($q) {
                          $q->whereNotNull('return_fee')
                            ->orWhereHas('statuses', function($statusQ) {
                                $statusQ->where('status', 'cancelled');
                            });
                      });
            } else {
                $query->where('status', $request->status);
            }
        }
        
        if ($request->has('warehouse_id') && $request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        if ($request->has('to_warehouse_id') && $request->to_warehouse_id) {
            $query->where('to_warehouse_id', $request->to_warehouse_id);
        }
        
        if ($request->has('driver_id') && $request->driver_id) {
            $query->where('delivery_driver_id', $request->driver_id);
        }
        
        $orders = $query->with([
                'customer', 
                'deliveryDriver', 
                'warehouse', 
                'toWarehouse', 
                'warehouseTransactions',
                'statuses' => function($q) {
                    $q->orderBy('created_at', 'desc');
                }
            ])
            ->orderByRaw('CASE 
                WHEN status = "delivered" AND delivered_at IS NOT NULL THEN delivered_at
                WHEN status IN ("failed", "cancelled") THEN updated_at
                ELSE created_at
            END DESC')
            ->get();
        
        if ($warehouseFilter) {
            $orders = $orders->filter(function($order) use ($warehouseFilter) {
                if ($order->warehouse_id == $warehouseFilter) return true;
                $firstOut = $order->warehouseTransactions->where('type', 'out')->sortBy('transaction_date')->first();
                return $firstOut && $firstOut->warehouse_id == $warehouseFilter;
            })->values();
        }
        
        $title = $type === 'delivered' ? 'Đơn hàng đã giao' : ($type === 'failed' ? 'Đơn hàng thất bại/hủy' : 'Tất cả đơn hàng');
        
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $drivers = Driver::where('is_active', true)->orderBy('name')->get();
        
        return view('admin.reports.detail', compact('orders', 'date', 'dateFrom', 'dateTo', 'type', 'title', 'warehouses', 'drivers'));
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
            $statsInPeriod = Order::where(function($q) use ($warehouse) {
                    $q->where('warehouse_id', $warehouse->id)
                      ->orWhereHas('warehouseTransactions', function($transQ) use ($warehouse) {
                          $transQ->where('warehouse_id', $warehouse->id)->where('type', 'out');
                      });
                })
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->selectRaw('
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered_orders,
                    SUM(CASE WHEN status = "in_warehouse" THEN 1 ELSE 0 END) as in_warehouse_orders,
                    SUM(CASE WHEN status = "in_transit" THEN 1 ELSE 0 END) as in_transit_orders,
                    SUM(shipping_fee) as total_shipping_revenue,
                    SUM(return_fee) as total_return_fee,
                    SUM(cod_amount) as total_cod_amount,
                    SUM(cod_collected) as total_cod_collected
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

            $totalRevenue = ($statsInPeriod->total_shipping_revenue ?? 0) + ($statsInPeriod->total_return_fee ?? 0);
            
            $warehouseStats[] = [
                'warehouse' => $warehouse,
                'current_inventory' => $currentInventory,
                'incoming_orders' => $incomingOrders,
                'total_orders' => $statsInPeriod->total_orders ?? 0,
                'delivered_orders' => $statsInPeriod->delivered_orders ?? 0,
                'in_warehouse_orders' => $statsInPeriod->in_warehouse_orders ?? 0,
                'in_transit_orders' => $statsInPeriod->in_transit_orders ?? 0,
                'total_shipping_revenue' => $statsInPeriod->total_shipping_revenue ?? 0,
                'total_return_fee' => $statsInPeriod->total_return_fee ?? 0,
                'total_revenue' => $totalRevenue,
                'total_cod_amount' => $statsInPeriod->total_cod_amount ?? 0,
                'total_cod_collected' => $statsInPeriod->total_cod_collected ?? 0,
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
            'total_return_fee' => array_sum(array_column($warehouseStats, 'total_return_fee')),
            'total_revenue' => array_sum(array_column($warehouseStats, 'total_revenue')),
            'total_cod_amount' => array_sum(array_column($warehouseStats, 'total_cod_amount')),
            'total_cod_collected' => array_sum(array_column($warehouseStats, 'total_cod_collected')),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'summary' => $totalStats,
                'warehouses' => $warehouseStats,
            ]);
        }

        return view('admin.reports.warehouses-overview', compact('warehouseStats', 'totalStats', 'dateFrom', 'dateTo'));
    }

    public function warehouseOrders(Request $request, string $warehouseId)
    {
        $warehouse = \App\Models\Warehouse::findOrFail($warehouseId);
        
        $dateFrom = $request->get('date_from', date('Y-m-d', strtotime('-30 days')));
        $dateTo = $request->get('date_to', date('Y-m-d'));
        
        $query = Order::where(function($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId)
              ->orWhereHas('warehouseTransactions', function($transQ) use ($warehouseId) {
                  $transQ->where('warehouse_id', $warehouseId);
              });
        })
        ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%")
                  ->orWhere('sender_phone', 'like', "%{$search}%")
                  ->orWhere('receiver_name', 'like', "%{$search}%")
                  ->orWhere('receiver_phone', 'like', "%{$search}%")
                  ->orWhere('receiver_address', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('status') && $request->status) {
            if ($request->status === 'cancelled_delivered') {
                $query->where('status', 'delivered')
                      ->where(function($q) {
                          $q->whereNotNull('return_fee')
                            ->orWhereHas('statuses', function($statusQ) {
                                $statusQ->where('status', 'cancelled');
                            });
                      });
            } else {
                $query->where('status', $request->status);
            }
        }
        
        if ($request->has('to_warehouse_id') && $request->to_warehouse_id) {
            $query->where('to_warehouse_id', $request->to_warehouse_id);
        }
        
        if ($request->has('driver_id') && $request->driver_id) {
            $query->where('delivery_driver_id', $request->driver_id);
        }
        
        $orders = $query->with([
                'customer',
                'deliveryDriver',
                'warehouse',
                'toWarehouse',
                'warehouseTransactions',
                'statuses' => function($q) {
                    $q->orderBy('created_at', 'desc');
                }
            ])
            ->orderByRaw('CASE 
                WHEN status = "delivered" THEN 1
                WHEN status = "in_warehouse" THEN 2
                WHEN status = "in_transit" THEN 3
                WHEN status = "out_for_delivery" THEN 4
                WHEN status = "failed" THEN 5
                WHEN status = "cancelled" THEN 6
                ELSE 7
            END')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $title = "Tất cả đơn hàng - Kho {$warehouse->name}";
        
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $drivers = Driver::where('is_active', true)->orderBy('name')->get();
        
        return view('admin.reports.warehouse-orders', compact('orders', 'warehouse', 'title', 'dateFrom', 'dateTo', 'warehouses', 'drivers'));
    }
    
    public function exportCSV(Request $request)
    {
        $exportType = $request->get('export_type', 'detail');
        
        if ($exportType === 'detail') {
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $date = $request->get('date');
            $searchType = $request->get('type', 'delivered');
            
            if (!$dateFrom && !$dateTo && !$date) {
                return redirect()->back()->with('error', 'Vui lòng chọn ngày');
            }
            
            if ($date && !$dateFrom && !$dateTo) {
                $dateFrom = $date;
                $dateTo = $date;
            }
            
            if (!$dateFrom) {
                $dateFrom = $dateTo;
            }
            if (!$dateTo) {
                $dateTo = $dateFrom;
            }
            
            $user = auth()->user();
            $warehouseFilter = null;
            if ($user && $user->isWarehouseAdmin() && $user->warehouse_id) {
                $warehouseFilter = $user->warehouse_id;
            }
            
            if ($searchType === 'delivered') {
                $query = Order::where('status', 'delivered')
                    ->whereNotNull('delivered_at')
                    ->whereBetween('delivered_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            } elseif ($searchType === 'failed') {
                $query = Order::whereIn('status', ['failed', 'cancelled'])
                    ->whereBetween('updated_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            } else {
                $query = Order::where(function($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                      ->orWhere(function($subQ) use ($dateFrom, $dateTo) {
                          $subQ->where('status', 'delivered')
                               ->whereNotNull('delivered_at')
                               ->whereBetween('delivered_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                      })
                      ->orWhere(function($subQ) use ($dateFrom, $dateTo) {
                          $subQ->whereIn('status', ['failed', 'cancelled'])
                               ->whereBetween('updated_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                      });
                });
            }
            
            if ($warehouseFilter) {
                $query->where(function($q) use ($warehouseFilter) {
                    $q->where('warehouse_id', $warehouseFilter)
                      ->orWhereHas('warehouseTransactions', function($transQ) use ($warehouseFilter) {
                          $transQ->where('warehouse_id', $warehouseFilter)->where('type', 'out');
                      });
                });
            }
            
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('tracking_number', 'like', "%{$search}%")
                      ->orWhere('sender_name', 'like', "%{$search}%")
                      ->orWhere('sender_phone', 'like', "%{$search}%")
                      ->orWhere('receiver_name', 'like', "%{$search}%")
                      ->orWhere('receiver_phone', 'like', "%{$search}%");
                });
            }
            
            $orders = $query->with(['customer', 'deliveryDriver', 'warehouse', 'toWarehouse'])->get();
            
            if ($dateFrom == $dateTo) {
                $filename = "bao_cao_chi_tiet_{$dateFrom}_{$searchType}.csv";
            } else {
                $filename = "bao_cao_chi_tiet_{$dateFrom}_to_{$dateTo}_{$searchType}.csv";
            }
        } else {
            $warehouseId = $request->get('warehouse_id');
            $dateFrom = $request->get('date_from', date('Y-m-d', strtotime('-30 days')));
            $dateTo = $request->get('date_to', date('Y-m-d'));
            
            if (!$warehouseId) {
                return redirect()->back()->with('error', 'Vui lòng chọn kho');
            }
            
            $warehouse = Warehouse::findOrFail($warehouseId);
            
            $query = Order::where(function($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                  ->orWhereHas('warehouseTransactions', function($transQ) use ($warehouseId) {
                      $transQ->where('warehouse_id', $warehouseId);
                  });
            })
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('tracking_number', 'like', "%{$search}%")
                      ->orWhere('sender_name', 'like', "%{$search}%")
                      ->orWhere('sender_phone', 'like', "%{$search}%")
                      ->orWhere('receiver_name', 'like', "%{$search}%")
                      ->orWhere('receiver_phone', 'like', "%{$search}%");
                });
            }
            
            if ($request->has('status') && $request->status) {
                if ($request->status === 'cancelled_delivered') {
                    $query->where('status', 'delivered')
                          ->where(function($q) {
                              $q->whereNotNull('return_fee')
                                ->orWhereHas('statuses', function($statusQ) {
                                    $statusQ->where('status', 'cancelled');
                                });
                          });
                } else {
                    $query->where('status', $request->status);
                }
            }
            
            if ($request->has('to_warehouse_id') && $request->to_warehouse_id) {
                $query->where('to_warehouse_id', $request->to_warehouse_id);
            }
            
            if ($request->has('driver_id') && $request->driver_id) {
                $query->where('delivery_driver_id', $request->driver_id);
            }
            
            $orders = $query->with(['customer', 'deliveryDriver', 'warehouse', 'toWarehouse', 'statuses'])->get();
            
            $filename = "bao_cao_kho_{$warehouse->code}_{$dateFrom}_to_{$dateTo}.csv";
        }
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, [
                'Mã vận đơn',
                'Người gửi',
                'SĐT người gửi',
                'Người nhận',
                'SĐT người nhận',
                'Địa chỉ nhận',
                'Tỉnh/TP',
                'Kho gửi',
                'Kho nhận',
                'Tài xế',
                'SĐT tài xế',
                'COD',
                'COD đã thu',
                'Phí VC',
                'Phí trả hàng',
                'Doanh thu',
                'Trạng thái',
                'Ngày tạo',
                'Ngày giao',
                'Ghi chú'
            ], ';');
            
            foreach ($orders as $order) {
                $isReturnOrder = false;
                $hasCancelledStatus = false;
                
                if ($order->return_fee && $order->return_fee > 0) {
                    $isReturnOrder = true;
                }
                
                if ($order->statuses && $order->statuses->isNotEmpty()) {
                    $hasCancelledStatus = $order->statuses->where('status', 'cancelled')->isNotEmpty();
                }
                
                $statusText = $order->status;
                if ($order->status === 'delivered' && ($isReturnOrder || $hasCancelledStatus)) {
                    $statusText = 'Đã hủy - Đã giao (trả hàng)';
                }
                
                $revenue = ($order->cod_collected ?? 0) + ($order->shipping_fee ?? 0) + ($order->return_fee ?? 0);
                
                fputcsv($file, [
                    $order->tracking_number,
                    $order->sender_name,
                    $order->sender_phone,
                    $order->receiver_name,
                    $order->receiver_phone,
                    $order->receiver_address,
                    $order->receiver_province,
                    $order->warehouse ? $order->warehouse->name : 'N/A',
                    $order->toWarehouse ? $order->toWarehouse->name : 'Giao trực tiếp',
                    $order->deliveryDriver ? $order->deliveryDriver->name : 'Chưa phân công',
                    $order->deliveryDriver ? $order->deliveryDriver->phone : '',
                    $order->cod_amount ?? 0,
                    $order->cod_collected ?? 0,
                    $order->shipping_fee ?? 0,
                    $order->return_fee ?? 0,
                    $revenue,
                    $statusText,
                    $order->created_at ? $order->created_at->format('d/m/Y H:i') : '',
                    $order->delivered_at ? $order->delivered_at->format('d/m/Y H:i') : '',
                    $order->delivery_notes ?? $order->notes ?? ''
                ], ';');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
