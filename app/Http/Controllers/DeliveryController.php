<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\WarehouseTransaction;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    /**
     * Display delivery index page
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // PHẦN 1: Đơn hàng đã xuất từ kho này - Phân công đi nơi khác
        // CHỈ hiển thị đơn hàng đang vận chuyển đến kho khác (có to_warehouse_id khác với warehouse_id hiện tại)
        // KHÔNG hiển thị đơn hàng đã phân công shipper để giao đến khách hàng (không có to_warehouse_id hoặc status = 'out_for_delivery')
        // KHÔNG hiển thị đơn hàng đã giao thành công (delivered) hoặc đã hủy (cancelled)
        // KHÔNG hiển thị đơn hàng đã được phân công tài xế (có delivery_driver_id) - vì đã được phân công rồi
        $ordersShippedOutQuery = Order::where('status', '!=', 'delivered')
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'failed')
            // BỎ điều kiện whereNull('delivery_driver_id') để đơn hàng vẫn hiển thị sau khi phân công tài xế
            ->where(function($q) use ($user) {
            if ($user->isWarehouseAdmin() && $user->warehouse_id) {
                $warehouse = \App\Models\Warehouse::find($user->warehouse_id);
                // Warehouse admin: chỉ xem đơn hàng đã xuất từ kho của họ để chuyển đến kho khác
                // QUAN TRỌNG: Tất cả các điều kiện con đều phải đảm bảo delivery_driver_id IS NULL (đã được áp dụng ở query chính)
                $q->where(function($subQ) use ($user) {
                    // Đơn hàng đang vận chuyển đến kho khác (in_transit với warehouse_id = kho này VÀ có to_warehouse_id khác)
                    $subQ->where('status', 'in_transit')
                         ->where('warehouse_id', $user->warehouse_id)
                         ->whereNotNull('to_warehouse_id')
                         ->where('to_warehouse_id', '!=', $user->warehouse_id)
                         ->whereNull('delivery_driver_id'); // Đảm bảo chưa phân công
                })
                ->orWhere(function($subQ) use ($user, $warehouse) {
                    // Đơn hàng đã xuất kho để chuyển đến kho khác
                    // (có transaction 'out' từ kho này VÀ có to_warehouse_id khác HOẶC receiver_province khác với province của kho)
                    $subQ->whereHas('warehouseTransactions', function($transQ) use ($user) {
                        $transQ->where('warehouse_id', $user->warehouse_id)
                               ->where('type', 'out')
                               ->whereDate('transaction_date', '>=', now()->subDays(30));
                    })
                    // BỎ whereNull('delivery_driver_id') để đơn hàng vẫn hiển thị sau khi phân công
                    ->where(function($wq) use ($user, $warehouse) {
                        // Có to_warehouse_id khác với kho hiện tại
                        $wq->where(function($tq) use ($user) {
                            $tq->whereNotNull('to_warehouse_id')
                               ->where('to_warehouse_id', '!=', $user->warehouse_id);
                        });
                        // HOẶC receiver_province khác với province của kho (nếu không có to_warehouse_id)
                        if ($warehouse && $warehouse->province) {
                            $wq->orWhere(function($pq) use ($warehouse) {
                                $pq->whereNull('to_warehouse_id')
                                   ->where('receiver_province', '!=', $warehouse->province)
                                   ->whereNotNull('receiver_province');
                            });
                        }
                    })
                    // Loại trừ đơn hàng đã được phân công shipper để giao đến khách hàng
                    ->where(function($sq) {
                        $sq->where('status', '!=', 'out_for_delivery')
                           ->orWhere(function($oq) {
                               $oq->where('status', 'out_for_delivery')
                                  ->whereNotNull('to_warehouse_id');
                           });
                    });
                });
            } else {
                // Admin: xem tất cả đơn hàng đã xuất kho để chuyển đến kho khác
                // QUAN TRỌNG: Tất cả các điều kiện con đều phải đảm bảo delivery_driver_id IS NULL (đã được áp dụng ở query chính)
                $q->where(function($subQ) {
                    $subQ->where('status', 'in_transit')
                         ->whereNotNull('to_warehouse_id');
                         // BỎ whereNull('delivery_driver_id') để đơn hàng vẫn hiển thị sau khi phân công
                })
                  ->orWhere(function($subQ) {
                      $subQ->where('status', 'in_warehouse')
                           ->whereHas('warehouseTransactions', function($transQ) {
                               $transQ->where('type', 'out')
                                      ->whereDate('transaction_date', '>=', now()->subDays(30));
                           })
                           ->whereNotNull('to_warehouse_id');
                           // BỎ whereNull('delivery_driver_id') để đơn hàng vẫn hiển thị sau khi phân công
                  })
                  ->orWhere(function($subQ) {
                      // Đơn hàng có transaction xuất kho và có to_warehouse_id (chuyển đến kho khác)
                      $subQ->whereHas('warehouseTransactions', function($transQ) {
                          $transQ->where('type', 'out')
                                 ->whereDate('transaction_date', '>=', now()->subDays(30));
                      })
                      ->whereNotNull('to_warehouse_id')
                      // Loại trừ đơn hàng đã được phân công shipper (BỎ whereNull('delivery_driver_id') để đơn hàng vẫn hiển thị sau khi phân công)
                      ->where(function($sq) {
                          $sq->where('status', '!=', 'out_for_delivery')
                             ->orWhere(function($oq) {
                                 $oq->where('status', 'out_for_delivery')
                                    ->whereNotNull('to_warehouse_id');
                             });
                      });
                  });
            }
        })
        ->with(['customer', 'deliveryDriver', 'warehouse', 'route', 'toWarehouse']);
        
        // Filter theo tỉnh nếu có
        if ($request->has('province_shipped_out') && $request->province_shipped_out) {
            $ordersShippedOutQuery->where('receiver_province', $request->province_shipped_out);
        }
        
        $ordersShippedOut = $ordersShippedOutQuery->orderBy('created_at', 'desc')->get();
        
        // Lọc lại để loại trừ đơn hàng đã được nhận vào kho đích (có transaction 'in' tại kho đích)
        // HOẶC đơn hàng đã giao thành công (delivered) hoặc đã hủy (cancelled)
        // BỎ điều kiện loại trừ đơn hàng đã được phân công tài xế - đơn hàng vẫn hiển thị sau khi phân công
        $ordersShippedOut = $ordersShippedOut->filter(function($order) use ($user) {
            // Nếu đơn hàng đã delivered hoặc cancelled, không hiển thị
            if ($order->status === 'delivered' || $order->status === 'cancelled' || $order->status === 'failed') {
                return false;
            }
            
            // BỎ điều kiện loại trừ đơn hàng đã được phân công tài xế - đơn hàng vẫn hiển thị để theo dõi
            
            // Nếu đơn hàng có to_warehouse_id, kiểm tra xem kho đích đã nhận chưa
            if ($order->to_warehouse_id) {
                $hasBeenReceived = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                    ->where('warehouse_id', $order->to_warehouse_id)
                    ->where('type', 'in')
                    ->where(function($q) {
                        $q->where('notes', 'like', '%Nhận từ%')
                          ->orWhere('notes', 'like', '%từ kho%')
                          ->orWhere('notes', 'like', '%Nhận từ kho%');
                    })
                    ->exists();
                // Nếu kho đích đã nhận, không hiển thị ở kho gửi nữa
                if ($hasBeenReceived) {
                    return false;
                }
            }
            
            // Nếu không có to_warehouse_id nhưng đã delivered, không hiển thị
            // (đơn hàng giao trực tiếp đến khách hàng đã hoàn thành)
            if (!$order->to_warehouse_id && $order->status === 'delivered') {
                return false;
            }
            
            return true;
        })->values();
        
        // PHẦN 2: Đơn hàng đang đến kho này - Nhận nơi khác về
        // CHỈ hiển thị đơn hàng CHƯA được nhận vào kho này
        // Loại trừ các đơn hàng đã có transaction 'in' tại kho này với notes "Nhận từ"
        $ordersIncomingQuery = Order::where(function($q) use ($user) {
                // Đơn hàng đang vận chuyển (in_transit) và chưa ở kho này
                $q->where(function($subQ) use ($user) {
                    $subQ->where('status', 'in_transit')
                         ->where(function($wq) use ($user) {
                             $wq->where('warehouse_id', '!=', $user->warehouse_id)
                                ->orWhereNull('warehouse_id');
                         });
                })
                  // HOẶC đơn hàng đã xuất kho từ kho khác (có transaction 'out' từ kho khác) nhưng CHƯA nhận vào kho này
                  ->orWhere(function($subQ) use ($user) {
                      $subQ->where('status', '!=', 'delivered')
                           ->where('status', '!=', 'cancelled')
                           ->where(function($wq) use ($user) {
                               $wq->where('warehouse_id', '!=', $user->warehouse_id)
                                  ->orWhereNull('warehouse_id');
                           })
                           ->whereHas('warehouseTransactions', function($transQ) use ($user) {
                               $transQ->where('type', 'out')
                                      ->where('warehouse_id', '!=', $user->warehouse_id)
                                      ->whereDate('transaction_date', '>=', now()->subDays(30));
                           })
                           // Loại trừ đơn hàng đã có transaction 'in' tại kho này
                           ->whereDoesntHave('warehouseTransactions', function($transQ) use ($user) {
                               $transQ->where('warehouse_id', $user->warehouse_id)
                                      ->where('type', 'in')
                                      ->where(function($notesQ) {
                                          $notesQ->where('notes', 'like', '%Nhận từ%')
                                                 ->orWhere('notes', 'like', '%từ kho%');
                                      });
                           });
                  });
            })
            ->with(['customer', 'deliveryDriver', 'warehouse', 'route', 'toWarehouse']);
        
        // Warehouse admin xem đơn hàng đang vận chuyển đến kho này
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $warehouse = \App\Models\Warehouse::find($user->warehouse_id);
            if ($warehouse) {
                // Tạo danh sách các tên tỉnh có thể khớp với kho này
                $provinceVariants = [];
                if ($warehouse->province) {
                    $provinceVariants[] = $warehouse->province;
                    $provinceVariants[] = str_replace(['Thành phố ', 'Tỉnh ', 'TP. ', 'TP '], '', $warehouse->province);
                }
                // Thêm các biến thể đặc biệt cho Hồ Chí Minh/Sài Gòn
                if ($warehouse->name && (stripos($warehouse->name, 'Sài Gòn') !== false || 
                    stripos($warehouse->name, 'Hồ Chí Minh') !== false || 
                    stripos($warehouse->name, 'HCM') !== false ||
                    stripos($warehouse->name, 'Ho Chi Minh') !== false)) {
                    $provinceVariants[] = 'Thành phố Hồ Chí Minh';
                    $provinceVariants[] = 'Hồ Chí Minh';
                    $provinceVariants[] = 'Sài Gòn';
                    $provinceVariants[] = 'TP. Hồ Chí Minh';
                    $provinceVariants[] = 'TP.HCM';
                }
                // Thêm các biến thể từ tên kho
                if ($warehouse->name) {
                    $provinceVariants[] = $warehouse->name;
                }
                
                $provinceVariants = array_unique($provinceVariants);
                
                $ordersIncomingQuery->where(function($q) use ($user, $warehouse, $provinceVariants) {
                    // Đang đến kho này (có to_warehouse_id = kho này) VÀ đã được phân công tài xế
                    $q->where(function($subQ) use ($user) {
                        $subQ->where('to_warehouse_id', $user->warehouse_id)
                             ->whereNotNull('delivery_driver_id');
                    });
                    
                    // HOẶC đang đến tỉnh của kho này (tìm tất cả các biến thể) VÀ đã được phân công tài xế
                    if (!empty($provinceVariants)) {
                        $q->orWhere(function($subQ) use ($provinceVariants) {
                            foreach ($provinceVariants as $variant) {
                                // Tìm chính xác hoặc tìm trong tên tỉnh
                                $subQ->orWhere('receiver_province', $variant)
                                     ->orWhere('receiver_province', 'like', '%' . $variant . '%');
                            }
                            // Đảm bảo đã được phân công tài xế
                            $subQ->whereNotNull('delivery_driver_id');
                        });
                    }
                });
            } else {
                // Nếu không có warehouse, chỉ xem đơn hàng đến kho này VÀ đã được phân công tài xế
                $ordersIncomingQuery->where('to_warehouse_id', $user->warehouse_id)
                    ->whereNotNull('delivery_driver_id');
            }
        }
        
        // Filter theo tỉnh nếu có
        if ($request->has('province_incoming') && $request->province_incoming) {
            $ordersIncomingQuery->where('receiver_province', $request->province_incoming);
        }
        
        $ordersIncoming = $ordersIncomingQuery->orderBy('created_at', 'desc')->get();
        
        // Giữ biến cũ để tương thích (tổng hợp cả 2 phần)
        $ordersInTransit = $ordersShippedOut->merge($ordersIncoming);
        
        // Đơn hàng sẵn sàng giao (đã phân công tài xế) - CHỈ hiển thị đơn hàng có delivery_driver_id
        $ordersReadyForDeliveryQuery = Order::where('status', 'out_for_delivery')
            ->whereNotNull('delivery_driver_id') // QUAN TRỌNG: Chỉ hiển thị đơn hàng đã phân công tài xế
            ->with(['customer', 'deliveryDriver', 'warehouse', 'toWarehouse']);
        
        // Warehouse admin xem đơn hàng đang giao trong khu vực của kho (dựa trên receiver_province)
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $warehouse = \App\Models\Warehouse::find($user->warehouse_id);
            if ($warehouse && $warehouse->province) {
                // Hiển thị đơn hàng đang giao đến tỉnh của kho này
                $ordersReadyForDeliveryQuery->where('receiver_province', $warehouse->province);
            } else {
                // Nếu không có province, fallback về warehouse_id
            $ordersReadyForDeliveryQuery->where('warehouse_id', $user->warehouse_id);
            }
        }
        
        // Filter theo tỉnh nếu có
        if ($request->has('province_delivery') && $request->province_delivery) {
            $ordersReadyForDeliveryQuery->where('receiver_province', $request->province_delivery);
        }
        
        $ordersReadyForDelivery = $ordersReadyForDeliveryQuery->orderBy('delivery_scheduled_at', 'asc')->get();
        
        // Đơn hàng trong kho chưa phân công tài xế giao hàng (in_warehouse và chưa có delivery_driver_id)
        // QUAN TRỌNG: Loại trừ đơn hàng đã xuất kho (có transaction xuất kho gần đây)
        $ordersInWarehouseQuery = Order::where('status', 'in_warehouse')
            ->whereNull('delivery_driver_id') // Chưa phân công tài xế giao hàng
            ->whereDoesntHave('warehouseTransactions', function($q) {
                // Loại trừ đơn hàng đã có transaction xuất kho (đã xuất kho)
                $q->where('type', 'out')
                  ->whereDate('transaction_date', '>=', now()->subDays(30)); // Trong 30 ngày gần đây
            })
            ->with(['customer', 'deliveryDriver', 'warehouse', 'route']);
        
        // Warehouse admin chỉ xem đơn hàng trong kho của mình
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $ordersInWarehouseQuery->where('warehouse_id', $user->warehouse_id);
        }
        
        // Filter theo tỉnh nếu có
        if ($request->has('province_warehouse') && $request->province_warehouse) {
            $ordersInWarehouseQuery->where('receiver_province', $request->province_warehouse);
        }
        
        $ordersInWarehouse = $ordersInWarehouseQuery->orderBy('created_at', 'desc')->get();
        
        // Đơn hàng đã nhận từ kho khác (có transaction 'in' với notes "Nhận từ...")
        // BAO GỒM cả đơn hàng đã phân công shipper (out_for_delivery) để có thể cập nhật giao hàng
        $ordersReceivedFromWarehousesQuery = Order::where(function($q) use ($user) {
            if ($user->isWarehouseAdmin() && $user->warehouse_id) {
                $q->where('warehouse_id', $user->warehouse_id);
            }
        })
        ->whereIn('status', ['in_warehouse', 'out_for_delivery'])
        ->whereHas('warehouseTransactions', function($transQ) use ($user) {
            $transQ->where('type', 'in')
                   ->where(function($notesQ) {
                       $notesQ->where('notes', 'like', '%Nhận từ%')
                              ->orWhere('notes', 'like', '%Nhận từ kho%')
                              ->orWhere('notes', 'like', '%từ kho%');
                   });
            if ($user->isWarehouseAdmin() && $user->warehouse_id) {
                $transQ->where('warehouse_id', $user->warehouse_id);
            }
        })
        ->with(['customer', 'deliveryDriver', 'warehouse', 'route', 'toWarehouse']);
        
        // Filter theo tỉnh nếu có
        if ($request->has('province_received') && $request->province_received) {
            $ordersReceivedFromWarehousesQuery->where('receiver_province', $request->province_received);
        }
        
        $ordersReceivedFromWarehouses = $ordersReceivedFromWarehousesQuery->orderByRaw('CASE WHEN status = "in_warehouse" THEN 0 ELSE 1 END')
            ->orderBy('updated_at', 'desc')
            ->get();
        
        // Tất cả đơn hàng cần giao (bao gồm cả đang vận chuyển và trong kho)
        $allOrdersQuery = Order::whereIn('status', ['in_warehouse', 'in_transit', 'out_for_delivery'])
            ->with(['customer', 'deliveryDriver', 'warehouse', 'route']);
        
        // Warehouse admin chỉ xem:
        // - Đơn hàng trong kho của mình (in_warehouse, out_for_delivery)
        // - Đơn hàng đang vận chuyển đến kho của mình (in_transit với to_warehouse_id)
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $allOrdersQuery->where(function($query) use ($user) {
                $query->where('warehouse_id', $user->warehouse_id)
                      ->orWhere(function($q) use ($user) {
                          $q->where('status', 'in_transit')
                            ->where('to_warehouse_id', $user->warehouse_id);
                      });
            });
        }
        
        $allOrders = $allOrdersQuery->orderByRaw("CASE 
                WHEN status = 'out_for_delivery' THEN 1 
                WHEN status = 'in_transit' THEN 2 
                ELSE 3 
            END")
            ->orderBy('delivery_scheduled_at', 'asc')
            ->get();
            
        // Lấy tài xế theo kho
        $driversQuery = \App\Models\Driver::where('is_active', true);
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $driversQuery->where('warehouse_id', $user->warehouse_id);
        }
        $drivers = $driversQuery->get();
        
        // Stats cho warehouse admin - đếm đơn hàng theo logic hiển thị thực tế
        $stats = [];
        
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $warehouse = \App\Models\Warehouse::find($user->warehouse_id);
            
            // Đếm đơn hàng đã xuất từ kho này để chuyển đến kho khác (có to_warehouse_id)
            $shippedOutQuery = Order::where(function($q) use ($user) {
                $q->where(function($subQ) use ($user) {
                    // Đơn hàng đang vận chuyển đến kho khác
                    $subQ->where('status', 'in_transit')
                         ->where('warehouse_id', $user->warehouse_id)
                         ->whereNotNull('to_warehouse_id')
                         ->where('to_warehouse_id', '!=', $user->warehouse_id);
                })
                ->orWhere(function($subQ) use ($user) {
                    // Đơn hàng đã xuất kho để chuyển đến kho khác
                    $warehouse = \App\Models\Warehouse::find($user->warehouse_id);
                    $subQ->whereHas('warehouseTransactions', function($transQ) use ($user) {
                        $transQ->where('warehouse_id', $user->warehouse_id)
                               ->where('type', 'out')
                               ->whereDate('transaction_date', '>=', now()->subDays(30));
                    })
                    ->where(function($wq) use ($user, $warehouse) {
                        $wq->where(function($tq) use ($user) {
                            $tq->whereNotNull('to_warehouse_id')
                               ->where('to_warehouse_id', '!=', $user->warehouse_id);
                        });
                        if ($warehouse && $warehouse->province) {
                            $wq->orWhere(function($pq) use ($warehouse) {
                                $pq->whereNull('to_warehouse_id')
                                   ->where('receiver_province', '!=', $warehouse->province)
                                   ->whereNotNull('receiver_province');
                            });
                        }
                    })
                    // Loại trừ đơn hàng đã được phân công shipper
                    ->where(function($sq) {
                        $sq->where('status', '!=', 'out_for_delivery')
                           ->orWhere(function($oq) {
                               $oq->where('status', 'out_for_delivery')
                                  ->whereNotNull('to_warehouse_id');
                           });
                    });
                });
            });
            $stats['shipped_out'] = $shippedOutQuery->count();
            
            // Đếm đơn hàng đang đến kho này (CHƯA được nhận VÀ đã được phân công tài xế)
            $incomingQuery = Order::where(function($q) use ($user) {
                    $q->where(function($subQ) use ($user) {
                        $subQ->where('status', 'in_transit')
                             ->where(function($wq) use ($user) {
                                 $wq->where('warehouse_id', '!=', $user->warehouse_id)
                                    ->orWhereNull('warehouse_id');
                             });
                    })
                      ->orWhere(function($subQ) use ($user) {
                          $subQ->where('status', '!=', 'delivered')
                               ->where('status', '!=', 'cancelled')
                               ->where(function($wq) use ($user) {
                                   $wq->where('warehouse_id', '!=', $user->warehouse_id)
                                      ->orWhereNull('warehouse_id');
                               })
                               ->whereHas('warehouseTransactions', function($transQ) use ($user) {
                                   $transQ->where('type', 'out')
                                          ->where('warehouse_id', '!=', $user->warehouse_id)
                                          ->whereDate('transaction_date', '>=', now()->subDays(30));
                               })
                               // Loại trừ đơn hàng đã có transaction 'in' tại kho này
                               ->whereDoesntHave('warehouseTransactions', function($transQ) use ($user) {
                                   $transQ->where('warehouse_id', $user->warehouse_id)
                                          ->where('type', 'in')
                                          ->where(function($notesQ) {
                                              $notesQ->where('notes', 'like', '%Nhận từ%')
                                                     ->orWhere('notes', 'like', '%từ kho%');
                                          });
                               });
                      });
                });
            
            if ($warehouse) {
                // Tạo danh sách các tên tỉnh có thể khớp với kho này
                $provinceVariants = [];
                if ($warehouse->province) {
                    $provinceVariants[] = $warehouse->province;
                    $provinceVariants[] = str_replace(['Thành phố ', 'Tỉnh ', 'TP. ', 'TP '], '', $warehouse->province);
                }
                // Thêm các biến thể đặc biệt cho Hồ Chí Minh/Sài Gòn
                if ($warehouse->name && (stripos($warehouse->name, 'Sài Gòn') !== false || 
                    stripos($warehouse->name, 'Hồ Chí Minh') !== false || 
                    stripos($warehouse->name, 'HCM') !== false ||
                    stripos($warehouse->name, 'Ho Chi Minh') !== false)) {
                    $provinceVariants[] = 'Thành phố Hồ Chí Minh';
                    $provinceVariants[] = 'Hồ Chí Minh';
                    $provinceVariants[] = 'Sài Gòn';
                    $provinceVariants[] = 'TP. Hồ Chí Minh';
                    $provinceVariants[] = 'TP.HCM';
                }
                $provinceVariants = array_unique($provinceVariants);
                
                $incomingQuery->where(function($q) use ($user, $warehouse, $provinceVariants) {
                    // Đang đến kho này (có to_warehouse_id = kho này) VÀ đã được phân công tài xế
                    $q->where(function($subQ) use ($user) {
                        $subQ->where('to_warehouse_id', $user->warehouse_id)
                             ->whereNotNull('delivery_driver_id');
                    });
                    
                    // HOẶC đang đến tỉnh của kho này (tìm tất cả các biến thể) VÀ đã được phân công tài xế
                    if (!empty($provinceVariants)) {
                        $q->orWhere(function($subQ) use ($provinceVariants) {
                            foreach ($provinceVariants as $variant) {
                                $subQ->orWhere('receiver_province', $variant)
                                     ->orWhere('receiver_province', 'like', '%' . $variant . '%');
                            }
                            // Đảm bảo đã được phân công tài xế
                            $subQ->whereNotNull('delivery_driver_id');
                        });
                    }
                });
            } else {
                // Nếu không có warehouse, chỉ xem đơn hàng đến kho này VÀ đã được phân công tài xế
                $incomingQuery->where('to_warehouse_id', $user->warehouse_id)
                    ->whereNotNull('delivery_driver_id');
            }
            $stats['incoming'] = $incomingQuery->count();
            
            // Tổng số đơn hàng đang vận chuyển (để tương thích với view cũ)
            $stats['in_transit'] = $stats['shipped_out'] + $stats['incoming'];
            
            // Đếm đơn hàng đang giao: đến tỉnh của kho này (dù xuất từ kho nào)
            $outForDeliveryQuery = Order::where('status', 'out_for_delivery')
                ->whereNotNull('delivery_driver_id');
            if ($warehouse && $warehouse->province) {
                $outForDeliveryQuery->where('receiver_province', $warehouse->province);
            } else {
                // Fallback về warehouse_id nếu không có province
                $outForDeliveryQuery->where('warehouse_id', $user->warehouse_id);
            }
            $stats['out_for_delivery'] = $outForDeliveryQuery->count();
            
            // Đếm đơn hàng đã giao hôm nay: đến tỉnh của kho này
            $deliveredQuery = Order::where('status', 'delivered')
                ->whereDate('delivered_at', today());
            if ($warehouse && $warehouse->province) {
                $deliveredQuery->where('receiver_province', $warehouse->province);
            } else {
                $deliveredQuery->where('warehouse_id', $user->warehouse_id);
            }
            $stats['delivered_today'] = $deliveredQuery->count();
            
            // Đếm đơn hàng thất bại hôm nay: đến tỉnh của kho này
            $failedQuery = Order::where('status', 'failed')
                ->whereDate('updated_at', today());
            if ($warehouse && $warehouse->province) {
                $failedQuery->where('receiver_province', $warehouse->province);
            } else {
                $failedQuery->where('warehouse_id', $user->warehouse_id);
            }
            $stats['failed_today'] = $failedQuery->count();
        } else {
            // Super admin hoặc không có warehouse_id: đếm tất cả
        $stats = [
                'in_transit' => Order::where('status', 'in_transit')->count(),
                'out_for_delivery' => Order::where('status', 'out_for_delivery')
                    ->whereNotNull('delivery_driver_id')
                ->count(),
                'delivered_today' => Order::where('status', 'delivered')
                ->whereDate('delivered_at', today())
                ->count(),
                'failed_today' => Order::where('status', 'failed')
                ->whereDate('updated_at', today())
                ->count(),
            ];
        }
        
        // Nếu thiếu stats, set mặc định
        if (!isset($stats['in_transit'])) {
            $stats['in_transit'] = 0;
        }
        if (!isset($stats['out_for_delivery'])) {
            $stats['out_for_delivery'] = 0;
        }
        if (!isset($stats['delivered_today'])) {
            $stats['delivered_today'] = 0;
        }
        if (!isset($stats['failed_today'])) {
            $stats['failed_today'] = 0;
        }
        
        if ($request->expectsJson()) {
            return response()->json($allOrders);
        }
        
        // Lấy thông tin kho của user để hiển thị trong view
        $userWarehouse = null;
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            $userWarehouse = \App\Models\Warehouse::find($user->warehouse_id);
        }
        
        return view('admin.delivery.index', compact('ordersShippedOut', 'ordersIncoming', 'ordersInTransit', 'ordersReadyForDelivery', 'ordersInWarehouse', 'ordersReceivedFromWarehouses', 'allOrders', 'drivers', 'stats', 'userWarehouse'));
    }
    
    /**
     * Get orders ready for delivery
     */
    public function readyForDelivery(Request $request)
    {
        $query = Order::whereIn('status', ['in_warehouse', 'in_transit', 'out_for_delivery'])
            ->with(['customer', 'deliveryDriver', 'warehouse']);

        if ($request->has('driver_id')) {
            $query->where('delivery_driver_id', $request->driver_id);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $orders = $query->orderBy('delivery_scheduled_at', 'asc')->paginate(20);

        return response()->json($orders);
    }

    /**
     * Assign delivery driver
     */
    public function assignDeliveryDriver(Request $request, string $id)
    {
        \Log::info('assignDeliveryDriver called', [
            'order_id' => $id,
            'driver_id' => $request->driver_id,
            'request_data' => $request->all()
        ]);
        
        $order = Order::findOrFail($id);
        $driver = \App\Models\Driver::findOrFail($request->driver_id);

        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'delivery_scheduled_at' => 'nullable|date',
        ]);
        
        $message = ''; // Khởi tạo biến message

        // Kiểm tra nếu đơn hàng đang vận chuyển đến kho khác (có to_warehouse_id)
        // Xử lý cả trường hợp status = 'in_warehouse' nhưng có to_warehouse_id (đã xuất kho nhưng chưa chuyển status)
        if ($order->to_warehouse_id && ($order->status === 'in_transit' || ($order->status === 'in_warehouse' && \App\Models\WarehouseTransaction::where('order_id', $order->id)->where('type', 'out')->exists()))) {
            // Đơn hàng đang vận chuyển đến kho khác, chưa đến kho đích
            // Chỉ phân công tài xế vận chuyển tỉnh (intercity_driver)
            if (!$driver->isIntercityDriver()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Đơn hàng đang vận chuyển đến kho khác. Chỉ có thể phân công tài xế vận chuyển tỉnh.',
                        'error' => 'invalid_driver_type'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Đơn hàng đang vận chuyển đến kho khác. Chỉ có thể phân công tài xế vận chuyển tỉnh.');
            }

            // Vẫn giữ status "in_transit" vì kho đích chưa nhận được hàng
            $order->update([
                'delivery_driver_id' => $validated['driver_id'],
                'delivery_scheduled_at' => $validated['delivery_scheduled_at'] ?? now(),
                // KHÔNG đổi status, vẫn là 'in_transit'
            ]);

            OrderStatus::create([
                'order_id' => $order->id,
                'status' => 'in_transit',
                'notes' => "Đã phân công tài xế vận chuyển tỉnh {$driver->name} vận chuyển đến kho đích (kho đích chưa nhận được hàng)",
                'driver_id' => $validated['driver_id'],
                'updated_by' => auth()->id(),
            ]);

            $message = 'Đã phân công tài xế vận chuyển tỉnh. Kho đích chưa nhận được hàng.';
        } elseif ($order->status === 'out_for_delivery' && $driver->isIntercityDriver()) {
            // Đơn hàng đang có status out_for_delivery nhưng được phân công tài xế vận chuyển tỉnh
            // Cần chuyển sang in_transit với to_warehouse_id
            if (!$order->warehouse_id) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Đơn hàng không có kho xác định',
                        'error' => 'missing_warehouse'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Đơn hàng không có kho xác định');
            }

            $warehouse = \App\Models\Warehouse::find($order->warehouse_id);
            if (!$warehouse || !$order->receiver_province || !$warehouse->province || $order->receiver_province === $warehouse->province) {
                // Không cần chuyển đến kho khác nếu cùng tỉnh hoặc không tìm thấy kho
                $order->update([
                    'delivery_driver_id' => $validated['driver_id'],
                    'delivery_scheduled_at' => $validated['delivery_scheduled_at'] ?? now(),
                ]);

                OrderStatus::create([
                    'order_id' => $order->id,
                    'status' => 'out_for_delivery',
                    'notes' => "Đã phân công lại tài xế vận chuyển tỉnh {$driver->name}",
                    'driver_id' => $validated['driver_id'],
                    'warehouse_id' => $order->warehouse_id,
                    'updated_by' => auth()->id(),
                ]);

                $message = 'Đã phân công lại tài xế giao hàng';
        } else {
                // Tìm kho đích (kho ở tỉnh người nhận)
                $targetWarehouse = \App\Models\Warehouse::where('is_active', true)
                    ->where('province', $order->receiver_province)
                    ->first();
                
                if ($targetWarehouse) {
                    // Chuyển đơn hàng sang in_transit với to_warehouse_id
                    $order->update([
                        'delivery_driver_id' => $validated['driver_id'],
                        'status' => 'in_transit',
                        'to_warehouse_id' => $targetWarehouse->id,
                        'delivery_scheduled_at' => $validated['delivery_scheduled_at'] ?? now(),
                    ]);

                    // Lấy thông tin kho gửi
                    $currentWarehouse = \App\Models\Warehouse::find($order->warehouse_id);
                    
                    OrderStatus::create([
                        'order_id' => $order->id,
                        'status' => 'in_transit',
                        'notes' => $currentWarehouse 
                            ? "Đơn hàng từ kho {$currentWarehouse->name} ({$currentWarehouse->province}) chuyển đến kho {$targetWarehouse->name} ({$targetWarehouse->province}) - Tài xế vận chuyển tỉnh: {$driver->name}"
                            : "Đơn hàng chuyển đến kho {$targetWarehouse->name} ({$targetWarehouse->province}) - Tài xế vận chuyển tỉnh: {$driver->name}",
                        'driver_id' => $validated['driver_id'],
                        'warehouse_id' => $order->warehouse_id,
                        'updated_by' => auth()->id(),
                    ]);

                    $message = "Đã phân công lại tài xế vận chuyển tỉnh. Đơn hàng sẽ được chuyển đến kho {$targetWarehouse->name}.";
                } else {
                    // Không tìm thấy kho đích, giữ nguyên status out_for_delivery
                    $order->update([
                        'delivery_driver_id' => $validated['driver_id'],
                        'delivery_scheduled_at' => $validated['delivery_scheduled_at'] ?? now(),
                    ]);

                    OrderStatus::create([
                        'order_id' => $order->id,
                        'status' => 'out_for_delivery',
                        'notes' => "Đã phân công lại tài xế vận chuyển tỉnh {$driver->name} (không tìm thấy kho đích tại {$order->receiver_province})",
                        'driver_id' => $validated['driver_id'],
                        'warehouse_id' => $order->warehouse_id,
                        'updated_by' => auth()->id(),
                    ]);

                    $message = 'Đã phân công lại tài xế giao hàng (không tìm thấy kho đích)';
                }
            }
        } else {
            // Kiểm tra nếu đơn hàng đã có transaction 'out' (đã xuất kho) nhưng status vẫn là 'in_warehouse'
            // Cần xử lý như đơn hàng đã xuất kho và cần phân công tài xế vận chuyển tỉnh
            $hasOutTransaction = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                ->where('type', 'out')
                ->whereDate('transaction_date', '>=', now()->subDays(30))
                ->exists();

            // Xử lý đơn hàng đã xuất kho (có transaction 'out') nhưng chưa có tài xế
            // Xử lý cả trường hợp status là 'in_warehouse' hoặc 'in_transit' hoặc các status khác
            if ($hasOutTransaction && !$order->delivery_driver_id) {
                // Kiểm tra đơn hàng phải có warehouse_id
                if (!$order->warehouse_id) {
                if ($request->expectsJson()) {
                    return response()->json([
                            'message' => 'Đơn hàng không có kho xác định',
                            'error' => 'missing_warehouse'
                        ], 400);
                    }
                    return redirect()->back()->with('error', 'Đơn hàng không có kho xác định');
                }

                $warehouse = \App\Models\Warehouse::find($order->warehouse_id);
                if (!$warehouse) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'Kho không tồn tại',
                            'error' => 'warehouse_not_found'
                        ], 400);
                    }
                    return redirect()->back()->with('error', 'Kho không tồn tại');
                }

                // Chỉ phân công tài xế vận chuyển tỉnh cho đơn hàng đã xuất kho
                if (!$driver->isIntercityDriver()) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'Đơn hàng đã xuất kho. Chỉ có thể phân công tài xế vận chuyển tỉnh.',
                            'error' => 'invalid_driver_type'
                        ], 400);
                    }
                    return redirect()->back()->with('error', 'Đơn hàng đã xuất kho. Chỉ có thể phân công tài xế vận chuyển tỉnh.');
                }

                // Tìm kho đích (kho ở tỉnh người nhận) - tìm linh hoạt hơn
                $targetWarehouse = null;
                if ($order->receiver_province && $warehouse->province && $order->receiver_province !== $warehouse->province) {
                    $receiverProvince = $order->receiver_province;
                    $receiverProvinceShort = str_replace(['Thành phố ', 'Tỉnh ', 'TP. ', 'TP '], '', $receiverProvince);
                    
                    // Tạo danh sách các biến thể tên tỉnh để tìm kho
                    $provinceVariants = [
                        $receiverProvince,
                        $receiverProvinceShort
                    ];
                    
                    // Thêm các biến thể đặc biệt cho Hồ Chí Minh/Sài Gòn
                    if (stripos($receiverProvince, 'Hồ Chí Minh') !== false || 
                        stripos($receiverProvince, 'HCM') !== false ||
                        stripos($receiverProvince, 'Ho Chi Minh') !== false ||
                        stripos($receiverProvince, 'Sài Gòn') !== false) {
                        $provinceVariants[] = 'Thành phố Hồ Chí Minh';
                        $provinceVariants[] = 'Hồ Chí Minh';
                        $provinceVariants[] = 'Sài Gòn';
                        $provinceVariants[] = 'TP. Hồ Chí Minh';
                        $provinceVariants[] = 'TP.HCM';
                    }
                    $provinceVariants = array_unique($provinceVariants);
                    
                    // Tìm kho đích: ưu tiên tìm chính xác theo province, sau đó tìm trong tên kho
                    $targetWarehouse = \App\Models\Warehouse::where('is_active', true)
                        ->where(function($q) use ($provinceVariants) {
                            foreach ($provinceVariants as $variant) {
                                $q->orWhere('province', $variant)
                                  ->orWhere('province', 'like', '%' . $variant . '%')
                                  ->orWhere('name', 'like', '%' . $variant . '%');
                            }
                        })
                        ->orderByRaw("CASE 
                            WHEN province = '" . addslashes($receiverProvince) . "' THEN 0 
                            WHEN name LIKE '%" . addslashes($receiverProvince) . "%' THEN 1 
                            WHEN name LIKE '%Sài Gòn%' THEN 2
                            WHEN province LIKE '%" . addslashes($receiverProvinceShort) . "%' THEN 3
                            ELSE 4 
                        END")
                        ->first();
                }

                $warehouseId = $order->warehouse_id;
                
                if ($targetWarehouse) {
                    // Chuyển đơn hàng sang in_transit với to_warehouse_id
                    $order->update([
                        'delivery_driver_id' => $validated['driver_id'],
                        'status' => 'in_transit',
                        'to_warehouse_id' => $targetWarehouse->id,
                        'delivery_scheduled_at' => $validated['delivery_scheduled_at'] ?? now(),
                    ]);

                    OrderStatus::create([
                        'order_id' => $order->id,
                        'status' => 'in_transit',
                        'notes' => "Đơn hàng từ kho {$warehouse->name} ({$warehouse->province}) chuyển đến kho {$targetWarehouse->name} ({$targetWarehouse->province}) - Tài xế vận chuyển tỉnh: {$driver->name}",
                        'driver_id' => $validated['driver_id'],
                        'warehouse_id' => $warehouseId,
                        'updated_by' => auth()->id(),
                    ]);

                    $message = "Đã phân công tài xế vận chuyển tỉnh. Đơn hàng sẽ được chuyển đến kho {$targetWarehouse->name}.";
                } else {
                    // Không tìm thấy kho đích, vẫn chuyển sang in_transit
                    $order->update([
                        'delivery_driver_id' => $validated['driver_id'],
                        'status' => 'in_transit',
                        'to_warehouse_id' => null,
                        'delivery_scheduled_at' => $validated['delivery_scheduled_at'] ?? now(),
                    ]);

                    OrderStatus::create([
                        'order_id' => $order->id,
                        'status' => 'in_transit',
                        'notes' => "Đơn hàng từ kho {$warehouse->name} ({$warehouse->province}) - Tài xế vận chuyển tỉnh: {$driver->name} (không tìm thấy kho đích tại {$order->receiver_province})",
                        'driver_id' => $validated['driver_id'],
                        'warehouse_id' => $warehouseId,
                        'updated_by' => auth()->id(),
                    ]);

                    $message = 'Đã phân công tài xế vận chuyển tỉnh (không tìm thấy kho đích)';
                }
            } else {
                // Đơn hàng ở kho (in_warehouse) - phân công tài xế
                // Cho phép phân công nếu status là 'in_warehouse' hoặc đã có transaction 'out' (đã xuất kho)
                $hasOutTransaction = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                    ->where('type', 'out')
                    ->whereDate('transaction_date', '>=', now()->subDays(30))
                    ->exists();
                
                if ($order->status !== 'in_warehouse' && !$hasOutTransaction) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'Chỉ có thể phân công tài xế giao hàng cho đơn hàng đang ở kho (in_warehouse) hoặc đã xuất kho',
                        'error' => 'invalid_status'
                    ], 400);
                }
                    return redirect()->back()->with('error', 'Chỉ có thể phân công tài xế giao hàng cho đơn hàng đang ở kho hoặc đã xuất kho');
                }

                // Kiểm tra đơn hàng phải có warehouse_id
                if (!$order->warehouse_id) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'Đơn hàng không có kho xác định',
                            'error' => 'missing_warehouse'
                        ], 400);
                    }
                    return redirect()->back()->with('error', 'Đơn hàng không có kho xác định');
                }

                $warehouse = \App\Models\Warehouse::find($order->warehouse_id);
                if (!$warehouse) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'Kho không tồn tại',
                            'error' => 'warehouse_not_found'
                        ], 400);
                    }
                    return redirect()->back()->with('error', 'Kho không tồn tại');
                }

                $warehouseId = $order->warehouse_id;
                
                // Kiểm tra nếu tài xế là vận chuyển tỉnh và đơn hàng cần chuyển đến kho khác
                if ($driver->isIntercityDriver() && $order->receiver_province && $warehouse->province && $order->receiver_province !== $warehouse->province) {
                    // Tìm kho đích (kho ở tỉnh người nhận) - tìm linh hoạt hơn
                    $receiverProvince = $order->receiver_province;
                    $receiverProvinceShort = str_replace(['Thành phố ', 'Tỉnh ', 'TP. ', 'TP '], '', $receiverProvince);
                    
                    // Tạo danh sách các biến thể tên tỉnh để tìm kho
                    $provinceVariants = [
                        $receiverProvince,
                        $receiverProvinceShort
                    ];
                    
                    // Thêm các biến thể đặc biệt cho Hồ Chí Minh/Sài Gòn
                    if (stripos($receiverProvince, 'Hồ Chí Minh') !== false || 
                        stripos($receiverProvince, 'HCM') !== false ||
                        stripos($receiverProvince, 'Ho Chi Minh') !== false ||
                        stripos($receiverProvince, 'Sài Gòn') !== false) {
                        $provinceVariants[] = 'Thành phố Hồ Chí Minh';
                        $provinceVariants[] = 'Hồ Chí Minh';
                        $provinceVariants[] = 'Sài Gòn';
                        $provinceVariants[] = 'TP. Hồ Chí Minh';
                        $provinceVariants[] = 'TP.HCM';
                    }
                    $provinceVariants = array_unique($provinceVariants);
                    
                    $targetWarehouse = \App\Models\Warehouse::where('is_active', true)
                        ->where(function($q) use ($provinceVariants) {
                            foreach ($provinceVariants as $variant) {
                                $q->orWhere('province', $variant)
                                  ->orWhere('province', 'like', '%' . $variant . '%')
                                  ->orWhere('name', 'like', '%' . $variant . '%');
                            }
                        })
                        ->orderByRaw("CASE 
                            WHEN province = '" . addslashes($receiverProvince) . "' THEN 0 
                            WHEN name LIKE '%" . addslashes($receiverProvince) . "%' THEN 1 
                            WHEN name LIKE '%Sài Gòn%' THEN 2
                            WHEN province LIKE '%" . addslashes($receiverProvinceShort) . "%' THEN 3
                            ELSE 4 
                        END")
                        ->first();
                    
                    if ($targetWarehouse) {
                        // Chuyển đơn hàng sang in_transit với to_warehouse_id
                        $order->update([
                            'delivery_driver_id' => $validated['driver_id'],
                            'status' => 'in_transit',
                            'to_warehouse_id' => $targetWarehouse->id,
                            'delivery_scheduled_at' => $validated['delivery_scheduled_at'] ?? now(),
                        ]);

                        OrderStatus::create([
                            'order_id' => $order->id,
                            'status' => 'in_transit',
                            'notes' => "Đơn hàng từ kho {$warehouse->name} ({$warehouse->province}) chuyển đến kho {$targetWarehouse->name} ({$targetWarehouse->province}) - Tài xế vận chuyển tỉnh: {$driver->name}",
                            'driver_id' => $validated['driver_id'],
                            'warehouse_id' => $warehouseId,
                            'updated_by' => auth()->id(),
                        ]);

                        // Tạo WarehouseTransaction để ghi nhận xuất kho (nếu chưa có từ lần xuất kho trước)
                        $existingTransaction = \App\Models\WarehouseTransaction::where('warehouse_id', $warehouseId)
                            ->where('order_id', $order->id)
                            ->where('type', 'out')
                            ->whereDate('transaction_date', today())
                            ->first();

                        if (!$existingTransaction) {
                            \App\Models\WarehouseTransaction::create([
                                'warehouse_id' => $warehouseId,
                                'order_id' => $order->id,
                                'type' => 'out',
                                'route_id' => $order->route_id ?? null,
                                'notes' => "Xuất kho vận chuyển đến kho {$targetWarehouse->name}",
                                'transaction_date' => now(),
                                'created_by' => auth()->id(),
                            ]);
            }

                        $message = "Đã phân công tài xế vận chuyển tỉnh. Đơn hàng sẽ được chuyển đến kho {$targetWarehouse->name}.";
                    } else {
                        // Không tìm thấy kho đích, giao trực tiếp
            $order->update([
                'delivery_driver_id' => $validated['driver_id'],
                'status' => 'out_for_delivery',
                            'to_warehouse_id' => null,
                            'delivery_scheduled_at' => $validated['delivery_scheduled_at'] ?? now(),
                        ]);

                        OrderStatus::create([
                            'order_id' => $order->id,
                            'status' => 'out_for_delivery',
                            'notes' => "Đã phân công tài xế vận chuyển tỉnh {$driver->name} giao hàng (không tìm thấy kho đích tại {$order->receiver_province})",
                            'driver_id' => $validated['driver_id'],
                            'warehouse_id' => $warehouseId,
                            'updated_by' => auth()->id(),
                        ]);

                        // Tạo WarehouseTransaction để ghi nhận xuất kho (nếu chưa có từ lần xuất kho trước)
                        $existingTransaction = \App\Models\WarehouseTransaction::where('warehouse_id', $warehouseId)
                            ->where('order_id', $order->id)
                            ->where('type', 'out')
                            ->whereDate('transaction_date', today())
                            ->first();

                        if (!$existingTransaction) {
                            \App\Models\WarehouseTransaction::create([
                                'warehouse_id' => $warehouseId,
                                'order_id' => $order->id,
                                'type' => 'out',
                                'route_id' => $order->route_id ?? null,
                                'notes' => "Xuất kho giao hàng (không tìm thấy kho đích tại {$order->receiver_province})",
                                'transaction_date' => now(),
                                'created_by' => auth()->id(),
                            ]);
                        }

                        $message = 'Đã phân công tài xế giao hàng (không tìm thấy kho đích)';
                    }
                } else {
                    // Tài xế shipper hoặc giao trong cùng tỉnh - giao trực tiếp đến khách hàng
                    $order->update([
                        'delivery_driver_id' => $validated['driver_id'],
                        'status' => 'out_for_delivery',
                        'to_warehouse_id' => null, // Đảm bảo không có to_warehouse_id khi giao hàng
                'delivery_scheduled_at' => $validated['delivery_scheduled_at'] ?? now(),
            ]);

            OrderStatus::create([
                'order_id' => $order->id,
                'status' => 'out_for_delivery',
                'notes' => 'Đã phân công tài xế giao hàng',
                'driver_id' => $validated['driver_id'],
                        'warehouse_id' => $warehouseId,
                'updated_by' => auth()->id(),
            ]);

                    // Tạo WarehouseTransaction để ghi nhận xuất kho (nếu chưa có từ lần xuất kho trước)
                    $existingTransaction = \App\Models\WarehouseTransaction::where('warehouse_id', $warehouseId)
                        ->where('order_id', $order->id)
                        ->where('type', 'out')
                        ->whereDate('transaction_date', today())
                        ->first();

                    if (!$existingTransaction) {
                        \App\Models\WarehouseTransaction::create([
                            'warehouse_id' => $warehouseId,
                            'order_id' => $order->id,
                            'type' => 'out',
                            'route_id' => $order->route_id ?? null,
                            'notes' => 'Đã phân công tài xế giao hàng - Xuất kho',
                            'transaction_date' => now(),
                            'created_by' => auth()->id(),
                        ]);
                    }

            $message = 'Đã phân công tài xế giao hàng';
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => $order->fresh(),
            ]);
        }
        
        // Redirect về trang delivery index để hiển thị đơn hàng đã được cập nhật
        return redirect()->route('admin.delivery.index')->with('success', $message);
    }
    
    /**
     * Bulk assign delivery driver for multiple orders
     */
    public function bulkAssignDeliveryDriver(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'assign_mode' => 'nullable|in:random,manual',
            'delivery_scheduled_at' => 'nullable|date',
        ]);

        $orderIds = $validated['order_ids'];
        $assignMode = $validated['assign_mode'] ?? 'manual';
        $scheduledAt = $validated['delivery_scheduled_at'] ?? now();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        $user = auth()->user();
        $warehouseId = $user->warehouse_id ?? null;
        
        // Nếu là random, lấy danh sách tài xế shipper của kho
        if ($assignMode === 'random') {
            $availableDrivers = \App\Models\Driver::where('is_active', true)
                ->where('driver_type', 'shipper')
                ->when($warehouseId, function($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                })
                ->get();
            
            if ($availableDrivers->isEmpty()) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Không có tài xế shipper nào khả dụng để phân công random'], 400);
                }
                return redirect()->back()->with('error', 'Không có tài xế shipper nào khả dụng để phân công random');
            }
        } else {
            // Manual mode - cần driver_id
            if (!$validated['driver_id']) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Vui lòng chọn tài xế'], 400);
                }
                return redirect()->back()->with('error', 'Vui lòng chọn tài xế');
            }
            $driver = \App\Models\Driver::findOrFail($validated['driver_id']);
            // KHÔNG kiểm tra driver type ở đây vì có thể là shipper hoặc intercity_driver tùy vào loại đơn hàng
        }

        foreach ($orderIds as $orderId) {
            try {
                $order = Order::findOrFail($orderId);

                \Log::info('Bulk assign processing order', [
                    'order_id' => $order->id,
                    'tracking_number' => $order->tracking_number,
                    'status' => $order->status,
                    'warehouse_id' => $order->warehouse_id,
                    'to_warehouse_id' => $order->to_warehouse_id,
                    'delivery_driver_id' => $order->delivery_driver_id,
                    'receiver_province' => $order->receiver_province,
                ]);

                // Kiểm tra xem đơn hàng đã được nhận vào kho hiện tại chưa (có transaction 'in' tại kho này)
                $hasInTransaction = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                    ->where('warehouse_id', $warehouseId)
                    ->where('type', 'in')
                    ->where(function($q) {
                        $q->where('notes', 'like', '%Nhận từ%')
                          ->orWhere('notes', 'like', '%từ kho%');
                    })
                    ->exists();

                // Kiểm tra nếu đơn hàng đã có transaction 'out' (đã xuất kho) nhưng status vẫn là 'in_warehouse'
                // Cần xử lý như đơn hàng đã xuất kho và cần phân công tài xế vận chuyển tỉnh
                $hasOutTransaction = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                    ->where('type', 'out')
                    ->whereDate('transaction_date', '>=', now()->subDays(30))
                    ->exists();
                    
                \Log::info('Order transaction check', [
                    'order_id' => $order->id,
                    'hasInTransaction' => $hasInTransaction,
                    'hasOutTransaction' => $hasOutTransaction,
                    'current_warehouse_id' => $warehouseId,
                ]);

                // Xử lý đơn hàng đang vận chuyển đến kho khác (in_transit với to_warehouse_id)
                // HOẶC đơn hàng đã xuất kho (có transaction 'out') VÀ có to_warehouse_id (đã xác định kho đích)
                // Nếu đơn hàng ĐÃ được nhận vào kho hiện tại (có transaction 'in'), thì không xử lý ở đây, sẽ xử lý ở phần dưới
                // QUAN TRỌNG: Kiểm tra cả trường hợp đơn hàng đã xuất kho từ kho hiện tại (warehouse_id = kho hiện tại)
                $hasOutFromCurrentWarehouse = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                    ->where('warehouse_id', $warehouseId)
                    ->where('type', 'out')
                    ->whereDate('transaction_date', '>=', now()->subDays(30))
                    ->exists();
                    
                // CHỈ xử lý như đơn hàng vận chuyển tỉnh nếu:
                // 1. Đơn hàng đang in_transit VÀ có to_warehouse_id
                // 2. HOẶC đơn hàng đã xuất kho VÀ có to_warehouse_id (đã xác định kho đích)
                // Nếu đơn hàng đã xuất kho nhưng CHƯA có to_warehouse_id, xử lý như đơn hàng trong kho bình thường (có thể dùng shipper hoặc intercity driver)
                if (($order->status === 'in_transit' && $order->to_warehouse_id && !$hasInTransaction) || 
                    ($order->status === 'in_warehouse' && $hasOutFromCurrentWarehouse && $order->to_warehouse_id && !$hasInTransaction && !$order->delivery_driver_id)) {
                    // Đơn hàng đang vận chuyển đến kho khác, chưa đến kho đích
                    // Chỉ phân công tài xế vận chuyển tỉnh (intercity_driver)
                    if ($assignMode === 'random') {
                        // Random mode: cần tìm intercity driver
                        $intercityDrivers = \App\Models\Driver::where('is_active', true)
                            ->where('driver_type', 'intercity_driver')
                            ->when($warehouseId, function($q) use ($warehouseId) {
                                $q->where('warehouse_id', $warehouseId);
                            })
                            ->get();
                        if ($intercityDrivers->isEmpty()) {
                            $failedCount++;
                            $errors[] = "Đơn hàng #{$order->tracking_number} đang vận chuyển đến kho khác. Không có tài xế vận chuyển tỉnh khả dụng.";
                            continue;
                        }
                        $driver = $intercityDrivers->random();
                    } else {
                        // Manual mode: kiểm tra driver đã chọn
                    if (!$driver->isIntercityDriver()) {
                        $failedCount++;
                        $errors[] = "Đơn hàng #{$order->tracking_number} đang vận chuyển đến kho khác. Chỉ có thể phân công tài xế vận chuyển tỉnh.";
                        continue;
                    }
                    }
                    
                    // Đảm bảo $driverId được định nghĩa
                    $driverId = $driver->id;

                    // Xác định kho đích - tìm linh hoạt hơn
                    $targetWarehouseId = $order->to_warehouse_id;
                    if (!$targetWarehouseId && $order->receiver_province) {
                        // Nếu chưa có to_warehouse_id, tìm kho đích dựa trên receiver_province
                        $receiverProvince = $order->receiver_province;
                        $receiverProvinceShort = str_replace(['Thành phố ', 'Tỉnh ', 'TP. ', 'TP '], '', $receiverProvince);
                        
                        // Tạo danh sách các biến thể tên tỉnh để tìm kho
                        $provinceVariants = [
                            $receiverProvince,
                            $receiverProvinceShort
                        ];
                        
                        // Thêm các biến thể đặc biệt cho Hồ Chí Minh/Sài Gòn
                        if (stripos($receiverProvince, 'Hồ Chí Minh') !== false || 
                            stripos($receiverProvince, 'HCM') !== false ||
                            stripos($receiverProvince, 'Ho Chi Minh') !== false ||
                            stripos($receiverProvince, 'Sài Gòn') !== false) {
                            $provinceVariants[] = 'Thành phố Hồ Chí Minh';
                            $provinceVariants[] = 'Hồ Chí Minh';
                            $provinceVariants[] = 'Sài Gòn';
                            $provinceVariants[] = 'TP. Hồ Chí Minh';
                            $provinceVariants[] = 'TP.HCM';
                        }
                        $provinceVariants = array_unique($provinceVariants);
                        
                        $targetWarehouse = \App\Models\Warehouse::where('is_active', true)
                            ->where(function($q) use ($provinceVariants) {
                                foreach ($provinceVariants as $variant) {
                                    $q->orWhere('province', $variant)
                                      ->orWhere('province', 'like', '%' . $variant . '%')
                                      ->orWhere('name', 'like', '%' . $variant . '%');
                                }
                            })
                            ->orderByRaw("CASE 
                                WHEN province = '" . addslashes($receiverProvince) . "' THEN 0 
                                WHEN name LIKE '%" . addslashes($receiverProvince) . "%' THEN 1 
                                WHEN name LIKE '%Sài Gòn%' THEN 2
                                WHEN province LIKE '%" . addslashes($receiverProvinceShort) . "%' THEN 3
                                ELSE 4 
                            END")
                            ->first();
                        if ($targetWarehouse) {
                            $targetWarehouseId = $targetWarehouse->id;
                        }
                    }

                    // Cập nhật đơn hàng
                    $updateData = [
                        'delivery_driver_id' => $driverId,
                        'delivery_scheduled_at' => $scheduledAt,
                    ];
                    
                    // Nếu đơn hàng đang ở trạng thái 'in_warehouse' nhưng đã xuất kho, chuyển sang 'in_transit'
                    if ($order->status === 'in_warehouse' && $hasOutTransaction) {
                        $updateData['status'] = 'in_transit';
                        if ($targetWarehouseId) {
                            $updateData['to_warehouse_id'] = $targetWarehouseId;
                        }
                    } else {
                        // Đơn hàng đã ở trạng thái 'in_transit', chỉ cập nhật tài xế
                        if ($targetWarehouseId && !$order->to_warehouse_id) {
                            $updateData['to_warehouse_id'] = $targetWarehouseId;
                        }
                    }
                    
                    $order->update($updateData);

                    // Lấy thông tin kho gửi và kho đích
                    $fromWarehouse = $order->warehouse_id ? \App\Models\Warehouse::find($order->warehouse_id) : null;
                    $finalToWarehouseId = $order->to_warehouse_id ?? $targetWarehouseId;
                    $toWarehouse = $finalToWarehouseId ? \App\Models\Warehouse::find($finalToWarehouseId) : null;
                    
                    $statusNotes = "Đơn hàng";
                    if ($fromWarehouse && $toWarehouse) {
                        $statusNotes .= " từ kho {$fromWarehouse->name} ({$fromWarehouse->province}) chuyển đến kho {$toWarehouse->name} ({$toWarehouse->province})";
                    } elseif ($toWarehouse) {
                        $statusNotes .= " chuyển đến kho {$toWarehouse->name} ({$toWarehouse->province})";
                    } elseif ($fromWarehouse) {
                        $statusNotes .= " từ kho {$fromWarehouse->name} ({$fromWarehouse->province})";
                    } else {
                        $statusNotes .= " đang vận chuyển đến kho đích";
                    }
                    $statusNotes .= " - Tài xế vận chuyển tỉnh: {$driver->name} (hàng loạt)";
                    
                    OrderStatus::create([
                        'order_id' => $order->id,
                        'status' => 'in_transit',
                        'notes' => $statusNotes,
                        'driver_id' => $driverId,
                        'warehouse_id' => $order->warehouse_id,
                        'updated_by' => auth()->id(),
                    ]);

                    $successCount++;
                    continue;
                }

                // Xử lý đơn hàng có status out_for_delivery nhưng được phân công tài xế vận chuyển tỉnh
                if ($order->status === 'out_for_delivery') {
                    // Kiểm tra driver type - chỉ xử lý nếu là intercity driver
                    $currentDriver = $driver;
                    if ($assignMode === 'random') {
                        // Random mode: cần tìm intercity driver
                        $intercityDrivers = \App\Models\Driver::where('is_active', true)
                            ->where('driver_type', 'intercity_driver')
                            ->when($warehouseId, function($q) use ($warehouseId) {
                                $q->where('warehouse_id', $warehouseId);
                            })
                            ->get();
                        if ($intercityDrivers->isEmpty()) {
                            $failedCount++;
                            $errors[] = "Đơn hàng #{$order->tracking_number} có status out_for_delivery. Không có tài xế vận chuyển tỉnh khả dụng.";
                            continue;
                        }
                        $currentDriver = $intercityDrivers->random();
                    } else {
                        // Manual mode: kiểm tra driver đã chọn
                        if (!$driver->isIntercityDriver()) {
                            $failedCount++;
                            $errors[] = "Đơn hàng #{$order->tracking_number} có status out_for_delivery. Chỉ có thể phân công tài xế vận chuyển tỉnh.";
                            continue;
                        }
                    }
                    
                    $driverId = $currentDriver->id;
                    
                    if (!$order->warehouse_id) {
                        $failedCount++;
                        $errors[] = "Đơn hàng #{$order->tracking_number} không có kho xác định";
                        continue;
                    }

                    $warehouse = \App\Models\Warehouse::find($order->warehouse_id);
                    if (!$warehouse || !$order->receiver_province || !$warehouse->province || $order->receiver_province === $warehouse->province) {
                        // Không cần chuyển đến kho khác
                    $order->update([
                        'delivery_driver_id' => $driverId,
                        'delivery_scheduled_at' => $scheduledAt,
                    ]);

                    OrderStatus::create([
                        'order_id' => $order->id,
                            'status' => 'out_for_delivery',
                            'notes' => "Đã phân công lại tài xế vận chuyển tỉnh {$currentDriver->name} (hàng loạt)",
                            'driver_id' => $driverId,
                            'warehouse_id' => $order->warehouse_id,
                            'updated_by' => auth()->id(),
                        ]);

                        $successCount++;
                        continue;
                    }

                    // Tìm kho đích (kho ở tỉnh người nhận) - tìm linh hoạt hơn
                    $receiverProvince = $order->receiver_province;
                    $receiverProvinceShort = str_replace(['Thành phố ', 'Tỉnh '], '', $receiverProvince);
                    
                    $targetWarehouse = \App\Models\Warehouse::where('is_active', true)
                        ->where(function($q) use ($receiverProvince, $receiverProvinceShort) {
                            // Tìm chính xác theo province
                            $q->where('province', $receiverProvince)
                              // Hoặc tìm trong tên kho (ví dụ: "Thành phố Hồ Chí Minh" vs "Hồ Chí Minh")
                              ->orWhere('name', 'like', '%' . $receiverProvince . '%')
                              ->orWhere('name', 'like', '%' . $receiverProvinceShort . '%')
                              ->orWhere('province', 'like', '%' . $receiverProvinceShort . '%');
                        })
                        ->orderByRaw("CASE 
                            WHEN province = '" . addslashes($receiverProvince) . "' THEN 0 
                            WHEN name LIKE '%" . addslashes($receiverProvince) . "%' THEN 1 
                            WHEN province LIKE '%" . addslashes($receiverProvinceShort) . "%' THEN 2
                            ELSE 3 
                        END")
                        ->first();
                    
                    if ($targetWarehouse) {
                        // Chuyển đơn hàng sang in_transit với to_warehouse_id
                        $order->update([
                            'delivery_driver_id' => $driverId,
                        'status' => 'in_transit',
                            'to_warehouse_id' => $targetWarehouse->id,
                            'delivery_scheduled_at' => $scheduledAt,
                        ]);

                        // Lấy thông tin kho gửi
                        $currentWarehouse = \App\Models\Warehouse::find($order->warehouse_id);
                        
                        OrderStatus::create([
                            'order_id' => $order->id,
                            'status' => 'in_transit',
                            'notes' => $currentWarehouse 
                                ? "Đơn hàng từ kho {$currentWarehouse->name} ({$currentWarehouse->province}) chuyển đến kho {$targetWarehouse->name} ({$targetWarehouse->province}) - Tài xế vận chuyển tỉnh: {$currentDriver->name} (hàng loạt)"
                                : "Đơn hàng chuyển đến kho {$targetWarehouse->name} ({$targetWarehouse->province}) - Tài xế vận chuyển tỉnh: {$currentDriver->name} (hàng loạt)",
                        'driver_id' => $driverId,
                            'warehouse_id' => $order->warehouse_id,
                        'updated_by' => auth()->id(),
                    ]);

                        $successCount++;
                        continue;
                } else {
                        // Không tìm thấy kho đích, giữ nguyên status
                    $order->update([
                        'delivery_driver_id' => $driverId,
                            'delivery_scheduled_at' => $scheduledAt,
                        ]);

                        OrderStatus::create([
                            'order_id' => $order->id,
                        'status' => 'out_for_delivery',
                            'notes' => "Đã phân công lại tài xế vận chuyển tỉnh {$currentDriver->name} (không tìm thấy kho đích tại {$order->receiver_province}) (hàng loạt)",
                            'driver_id' => $driverId,
                            'warehouse_id' => $order->warehouse_id,
                            'updated_by' => auth()->id(),
                        ]);

                        $successCount++;
                        continue;
                    }
                }

                // Xử lý đơn hàng đang ở kho (in_warehouse) - phân công tài xế
                // HOẶC đơn hàng đã xuất kho (có transaction 'out') nhưng status vẫn là 'in_warehouse' và chưa có tài xế
                if ($order->status === 'in_warehouse' || ($hasOutFromCurrentWarehouse && !$order->delivery_driver_id)) {
                    if (!$order->warehouse_id) {
                        $failedCount++;
                        $errors[] = "Đơn hàng #{$order->tracking_number} không có kho xác định";
                        continue;
                    }

                    $warehouse = \App\Models\Warehouse::find($order->warehouse_id);
                    if (!$warehouse) {
                        $failedCount++;
                        $errors[] = "Đơn hàng #{$order->tracking_number} - Kho không tồn tại";
                        continue;
                    }

                    $orderWarehouseId = $order->warehouse_id;
                    
                    // Kiểm tra xem đơn hàng có phải là đơn hàng từ kho khác tới không (đã được nhận vào kho)
                    $isOrderFromOtherWarehouse = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                        ->where('warehouse_id', $orderWarehouseId)
                        ->where('type', 'in')
                        ->where(function($q) {
                            $q->where('notes', 'like', '%Nhận từ%')
                              ->orWhere('notes', 'like', '%từ kho%');
                        })
                        ->exists();
                    
                    // Nếu đơn hàng từ kho khác tới, chỉ phân công shipper để giao đến khách hàng
                    if ($isOrderFromOtherWarehouse) {
                        // Đảm bảo tài xế là shipper
                        if ($assignMode === 'random') {
                            // Random mode: đã có availableDrivers là shipper (đã kiểm tra ở đầu hàm)
                            $driver = $availableDrivers->random();
                        } else {
                            // Manual mode: kiểm tra driver đã chọn
                            if (!$driver->isShipper()) {
                                $failedCount++;
                                $errors[] = "Đơn hàng #{$order->tracking_number} từ kho khác tới. Chỉ có thể phân công tài xế shipper để giao đến khách hàng.";
                                continue;
                            }
                        }
                        
                        $driverId = $driver->id;
                        
                        // Phân công shipper để giao đến khách hàng
                        $order->update([
                            'delivery_driver_id' => $driverId,
                            'status' => 'out_for_delivery',
                            'to_warehouse_id' => null, // Giao trực tiếp đến khách hàng, không đến kho khác
                            'delivery_scheduled_at' => $scheduledAt,
                        ]);

                        OrderStatus::create([
                            'order_id' => $order->id,
                            'status' => 'out_for_delivery',
                            'notes' => "Đã phân công tài xế shipper {$driver->name} để giao hàng đến người nhận (đơn hàng từ kho khác tới) (hàng loạt)",
                            'driver_id' => $driverId,
                            'warehouse_id' => $orderWarehouseId,
                            'updated_by' => auth()->id(),
                        ]);

                        $successCount++;
                        continue;
                    }
                    
                    // Nếu không phải đơn hàng từ kho khác tới, xử lý như bình thường
                    // Nếu là random mode, chọn tài xế ngẫu nhiên từ danh sách shipper
                    $currentDriver = $driver;
                    if ($assignMode === 'random') {
                        // Random mode: đã có availableDrivers là shipper (đã kiểm tra ở đầu hàm)
                        $currentDriver = $availableDrivers->random();
                    }
                    
                    $driverId = $currentDriver->id;

                    // Kiểm tra nếu tài xế là vận chuyển tỉnh và đơn hàng cần chuyển đến kho khác
                    if ($currentDriver->isIntercityDriver() && $order->receiver_province && $warehouse->province && $order->receiver_province !== $warehouse->province) {
                        // Tìm kho đích (kho ở tỉnh người nhận) - tìm linh hoạt hơn
                        $receiverProvince = $order->receiver_province;
                        $receiverProvinceShort = str_replace(['Thành phố ', 'Tỉnh ', 'TP. ', 'TP '], '', $receiverProvince);
                        
                        // Tạo danh sách các biến thể tên tỉnh để tìm kho
                        $provinceVariants = [
                            $receiverProvince,
                            $receiverProvinceShort
                        ];
                        
                        // Thêm các biến thể đặc biệt cho Hồ Chí Minh/Sài Gòn
                        if (stripos($receiverProvince, 'Hồ Chí Minh') !== false || 
                            stripos($receiverProvince, 'HCM') !== false ||
                            stripos($receiverProvince, 'Ho Chi Minh') !== false ||
                            stripos($receiverProvince, 'Sài Gòn') !== false) {
                            $provinceVariants[] = 'Thành phố Hồ Chí Minh';
                            $provinceVariants[] = 'Hồ Chí Minh';
                            $provinceVariants[] = 'Sài Gòn';
                            $provinceVariants[] = 'TP. Hồ Chí Minh';
                            $provinceVariants[] = 'TP.HCM';
                        }
                        $provinceVariants = array_unique($provinceVariants);
                        
                        $targetWarehouse = \App\Models\Warehouse::where('is_active', true)
                            ->where(function($q) use ($provinceVariants) {
                                foreach ($provinceVariants as $variant) {
                                    $q->orWhere('province', $variant)
                                      ->orWhere('province', 'like', '%' . $variant . '%')
                                      ->orWhere('name', 'like', '%' . $variant . '%');
                                }
                            })
                            ->orderByRaw("CASE 
                                WHEN province = '" . addslashes($receiverProvince) . "' THEN 0 
                                WHEN name LIKE '%" . addslashes($receiverProvince) . "%' THEN 1 
                                WHEN name LIKE '%Sài Gòn%' THEN 2
                                WHEN province LIKE '%" . addslashes($receiverProvinceShort) . "%' THEN 3
                                ELSE 4 
                            END")
                            ->first();
                        
                        if ($targetWarehouse) {
                            // Chuyển đơn hàng sang in_transit với to_warehouse_id
                            $order->update([
                                'delivery_driver_id' => $driverId,
                                'status' => 'in_transit',
                                'to_warehouse_id' => $targetWarehouse->id,
                                'delivery_scheduled_at' => $scheduledAt,
                            ]);

                            OrderStatus::create([
                                'order_id' => $order->id,
                                'status' => 'in_transit',
                                'notes' => "Đơn hàng từ kho {$warehouse->name} ({$warehouse->province}) chuyển đến kho {$targetWarehouse->name} ({$targetWarehouse->province}) - Tài xế vận chuyển tỉnh: {$currentDriver->name} (hàng loạt)",
                                'driver_id' => $driverId,
                                'warehouse_id' => $warehouseId,
                                'updated_by' => auth()->id(),
                            ]);

                            // Tạo WarehouseTransaction để ghi nhận xuất kho (nếu chưa có từ lần xuất kho trước)
                            $existingTransaction = \App\Models\WarehouseTransaction::where('warehouse_id', $warehouseId)
                                ->where('order_id', $order->id)
                                ->where('type', 'out')
                                ->whereDate('transaction_date', today())
                                ->first();

                            if (!$existingTransaction) {
                                \App\Models\WarehouseTransaction::create([
                                    'warehouse_id' => $orderWarehouseId,
                                    'order_id' => $order->id,
                                    'type' => 'out',
                                    'route_id' => $order->route_id ?? null,
                                    'notes' => "Xuất kho vận chuyển đến kho {$targetWarehouse->name} (hàng loạt)",
                                    'transaction_date' => now(),
                                    'created_by' => auth()->id(),
                                ]);
                            }

                            $successCount++;
                            continue;
                        } else {
                            // Không tìm thấy kho đích, giao trực tiếp
                            $order->update([
                                'delivery_driver_id' => $driverId,
                                'status' => 'out_for_delivery',
                                'to_warehouse_id' => null,
                                'delivery_scheduled_at' => $scheduledAt,
                            ]);

                            OrderStatus::create([
                                'order_id' => $order->id,
                                'status' => 'out_for_delivery',
                                'notes' => "Đã phân công tài xế vận chuyển tỉnh {$currentDriver->name} giao hàng (không tìm thấy kho đích tại {$order->receiver_province}) (hàng loạt)",
                                'driver_id' => $driverId,
                                'warehouse_id' => $orderWarehouseId,
                                'updated_by' => auth()->id(),
                            ]);

                            // Tạo WarehouseTransaction để ghi nhận xuất kho (nếu chưa có từ lần xuất kho trước)
                            $existingTransaction = \App\Models\WarehouseTransaction::where('warehouse_id', $orderWarehouseId)
                                ->where('order_id', $order->id)
                                ->where('type', 'out')
                                ->whereDate('transaction_date', today())
                                ->first();

                            if (!$existingTransaction) {
                                \App\Models\WarehouseTransaction::create([
                                    'warehouse_id' => $orderWarehouseId,
                                    'order_id' => $order->id,
                                    'type' => 'out',
                                    'route_id' => $order->route_id ?? null,
                                    'notes' => "Xuất kho giao hàng (không tìm thấy kho đích tại {$order->receiver_province}) (hàng loạt)",
                                    'transaction_date' => now(),
                                    'created_by' => auth()->id(),
                                ]);
                            }

                            $successCount++;
                            continue;
                        }
                    } else {
                        // Tài xế shipper hoặc giao trong cùng tỉnh - giao trực tiếp đến khách hàng
                        // Nếu là random mode, chọn tài xế ngẫu nhiên
                        if ($assignMode === 'random') {
                            $driver = $availableDrivers->random();
                            $driverId = $driver->id;
                        } else {
                            $driverId = $validated['driver_id'];
                        }
                        
                        $order->update([
                            'delivery_driver_id' => $driverId,
                            'status' => 'out_for_delivery',
                            'to_warehouse_id' => null, // Đảm bảo không có to_warehouse_id khi giao hàng
                        'delivery_scheduled_at' => $scheduledAt,
                    ]);

                    OrderStatus::create([
                        'order_id' => $order->id,
                        'status' => 'out_for_delivery',
                        'notes' => 'Đã phân công tài xế giao hàng (hàng loạt)',
                        'driver_id' => $driverId,
                            'warehouse_id' => $orderWarehouseId,
                        'updated_by' => auth()->id(),
                        ]);

                        // Tạo WarehouseTransaction để ghi nhận xuất kho (nếu chưa có từ lần xuất kho trước)
                        $existingTransaction = \App\Models\WarehouseTransaction::where('warehouse_id', $orderWarehouseId)
                            ->where('order_id', $order->id)
                            ->where('type', 'out')
                            ->whereDate('transaction_date', today())
                            ->first();

                        if (!$existingTransaction) {
                            \App\Models\WarehouseTransaction::create([
                                'warehouse_id' => $orderWarehouseId,
                                'order_id' => $order->id,
                                'type' => 'out',
                                'route_id' => $order->route_id ?? null,
                                'notes' => 'Đã phân công tài xế giao hàng - Xuất kho (hàng loạt)',
                                'transaction_date' => now(),
                                'created_by' => auth()->id(),
                    ]);
                }

                $successCount++;
                        continue;
                    }
                }

                // Trạng thái không hợp lệ - log chi tiết để debug
                \Log::warning('Order status not valid for assignment', [
                    'order_id' => $order->id,
                    'tracking_number' => $order->tracking_number,
                    'status' => $order->status,
                    'warehouse_id' => $order->warehouse_id,
                    'to_warehouse_id' => $order->to_warehouse_id,
                    'delivery_driver_id' => $order->delivery_driver_id,
                    'hasInTransaction' => $hasInTransaction,
                    'hasOutTransaction' => $hasOutTransaction,
                    'hasOutFromCurrentWarehouse' => $hasOutFromCurrentWarehouse ?? false,
                    'current_warehouse_id' => $warehouseId,
                    'driver_id' => $validated['driver_id'] ?? null,
                    'assign_mode' => $assignMode,
                ]);
                
                $failedCount++;
                $errorMsg = "Đơn hàng #{$order->tracking_number} không ở trạng thái hợp lệ để phân công tài xế";
                if (isset($hasOutFromCurrentWarehouse) && $hasOutFromCurrentWarehouse) {
                    $errorMsg .= " (Đã xuất kho từ kho hiện tại)";
                }
                $errors[] = $errorMsg . " (Status: {$order->status}, Warehouse: {$order->warehouse_id})";
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Lỗi khi phân công đơn hàng #{$orderId}: " . $e->getMessage();
            }
        }

        if ($request->expectsJson()) {
            $response = [
                'message' => "Đã phân công tài xế cho {$successCount} đơn hàng" . ($failedCount > 0 ? ", {$failedCount} đơn thất bại" : ''),
                'data' => ['success' => $successCount, 'failed' => $failedCount],
            ];
            
            if (!empty($errors)) {
                $response['errors'] = $errors;
                // Log chi tiết các lỗi
                \Log::warning('Bulk assign errors', [
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'errors' => $errors
                ]);
            }
            
            return response()->json($response);
        }
        
        // Redirect về trang delivery index để hiển thị đơn hàng đã được cập nhật
        return redirect()->route('admin.delivery.index')->with('success', "Đã phân công tài xế cho {$successCount} đơn hàng" . ($failedCount > 0 ? ", {$failedCount} đơn thất bại" : ''));
    }

    /**
     * Update delivery status
     */
    public function updateDeliveryStatus(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:delivered,failed',
            'delivery_notes' => 'nullable|string',
            'failure_reason' => 'required_if:status,failed|string',
            'cod_collected' => 'nullable|numeric|min:0',
            'shipping_fee' => 'required_if:status,delivered|nullable|numeric|min:0', // Phí vận chuyển - BẮT BUỘC nhập khi giao hàng thành công
        ]);

        $order->update([
            'status' => $validated['status'],
            'delivery_notes' => $validated['delivery_notes'] ?? null,
            'failure_reason' => $validated['failure_reason'] ?? null,
        ]);

        if ($validated['status'] === 'delivered') {
            // Cập nhật COD collected
            $codCollected = $validated['cod_collected'] ?? $order->cod_amount;
            
            // Cập nhật phí vận chuyển khi giao hàng thành công
            // Nếu có nhập mới thì dùng giá trị mới, nếu không thì giữ giá trị cũ (phí ước tính)
            $shippingFee = $validated['shipping_fee'] ?? $order->shipping_fee ?? 0;
            
            // Tính doanh thu: COD collected + Shipping fee
            // Doanh thu = COD đã thu + Phí vận chuyển
            $revenue = $codCollected + $shippingFee;
            
            $order->update([
                'delivered_at' => now(),
                'cod_collected' => $codCollected,
                'shipping_fee' => $shippingFee, // Lưu phí vận chuyển khi giao hàng thành công
                // Lưu doanh thu vào database để dễ truy vấn
                // Doanh thu = COD đã thu + Phí vận chuyển
            ]);
            
            // Ghi chú: Doanh thu được tính từ cod_collected + shipping_fee
            // Có thể truy vấn doanh thu bằng: cod_collected + shipping_fee
        }

        OrderStatus::create([
            'order_id' => $order->id,
            'status' => $validated['status'],
            'notes' => $validated['delivery_notes'] ?? ($validated['failure_reason'] ?? null),
            'driver_id' => $order->delivery_driver_id,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Trạng thái giao hàng đã được cập nhật',
            'data' => $order->fresh(),
        ]);
    }

    /**
     * Get delivery statistics for driver
     */
    public function getDriverStatistics(Request $request)
    {
        $driverId = $request->get('driver_id', auth()->id());

        $stats = [
            'today_delivered' => Order::where('delivery_driver_id', $driverId)
                ->where('status', 'delivered')
                ->whereDate('delivered_at', today())
                ->count(),
            'today_failed' => Order::where('delivery_driver_id', $driverId)
                ->where('status', 'failed')
                ->whereDate('updated_at', today())
                ->count(),
            'pending_deliveries' => Order::where('delivery_driver_id', $driverId)
                ->whereIn('status', ['out_for_delivery', 'in_transit'])
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Display delivered orders from receiving warehouses
     * Hiển thị đơn hàng đã giao thành công của các kho nhận
     */
    public function deliveredOrders(Request $request)
    {
        $user = auth()->user();
        
        // Lấy tất cả đơn hàng đã giao thành công (delivered)
        $deliveredOrdersQuery = Order::where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->with(['customer', 'deliveryDriver', 'warehouse', 'route', 'toWarehouse', 'pickupDriver']);
        
        // Nếu là warehouse admin, chỉ xem đơn hàng của kho mình
        if ($user->isWarehouseAdmin() && $user->warehouse_id) {
            // Lấy đơn hàng đã được nhận vào kho này (có transaction 'in' tại kho này)
            $ordersReceivedFromWarehouses = WarehouseTransaction::where('warehouse_id', $user->warehouse_id)
                ->where('type', 'in')
                ->where(function($q) {
                    $q->where('notes', 'like', '%Nhận từ%')
                      ->orWhere('notes', 'like', '%từ kho%')
                      ->orWhere('notes', 'like', '%Nhận từ kho%');
                })
                ->pluck('order_id')
                ->toArray();
            
            // Chỉ lấy đơn hàng đã được nhận vào kho này VÀ đã giao thành công
            $deliveredOrdersQuery->whereIn('id', $ordersReceivedFromWarehouses);
        }
        
        // Filter theo ngày giao hàng
        if ($request->has('date_from') && $request->date_from) {
            $deliveredOrdersQuery->whereDate('delivered_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $deliveredOrdersQuery->whereDate('delivered_at', '<=', $request->date_to);
        }
        
        // Filter theo mã vận đơn
        if ($request->has('tracking_number') && $request->tracking_number) {
            $deliveredOrdersQuery->where('tracking_number', 'like', '%' . $request->tracking_number . '%');
        }
        
        // Filter theo tài xế
        if ($request->has('driver_id') && $request->driver_id) {
            $deliveredOrdersQuery->where('delivery_driver_id', $request->driver_id);
        }
        
        $deliveredOrders = $deliveredOrdersQuery->orderBy('delivered_at', 'desc')->paginate(20);
        
        // Lấy danh sách tài xế để filter
        $drivers = \App\Models\Driver::where('is_active', true)
            ->where('driver_type', 'shipper')
            ->when($user->isWarehouseAdmin() && $user->warehouse_id, function($q) use ($user) {
                $q->where('warehouse_id', $user->warehouse_id);
            })
            ->orderBy('name')
            ->get();
        
        // Tính tổng doanh thu
        $totalRevenue = $deliveredOrders->sum(function($order) {
            return ($order->cod_collected ?? $order->cod_amount ?? 0) + ($order->shipping_fee ?? 0);
        });
        
        // Tính tổng COD đã thu
        $totalCodCollected = $deliveredOrders->sum(function($order) {
            return $order->cod_collected ?? $order->cod_amount ?? 0;
        });
        
        // Tính tổng phí vận chuyển
        $totalShippingFee = $deliveredOrders->sum('shipping_fee');
        
        if ($request->expectsJson()) {
            return response()->json([
                'data' => $deliveredOrders,
                'total_revenue' => $totalRevenue,
                'total_cod_collected' => $totalCodCollected,
                'total_shipping_fee' => $totalShippingFee,
            ]);
        }
        
        return view('admin.delivery.delivered', compact('deliveredOrders', 'drivers', 'totalRevenue', 'totalCodCollected', 'totalShippingFee'));
    }
}
