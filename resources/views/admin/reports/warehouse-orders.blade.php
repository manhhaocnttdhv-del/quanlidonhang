@extends('admin.layout')

@section('title', 'Chi tiết đơn hàng kho')
@section('page-title', 'Chi tiết đơn hàng kho')

@section('content')
<div class="card mb-4">
    <div class="card-header">
        <form method="GET" action="{{ route('admin.reports.warehouse-orders', $warehouse->id) }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Từ ngày</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Đến ngày</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Tìm kiếm
                </button>
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <div class="btn-group w-100">
                    <a href="{{ route('admin.reports.warehouses-overview') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                    </a>
                    <a href="{{ route('admin.reports.export-csv', ['export_type' => 'warehouse', 'warehouse_id' => $warehouse->id, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-success">
                        <i class="fas fa-file-csv me-2"></i>Export CSV
                    </a>
                </div>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">{{ $title }}</h5>
                <small class="text-muted">Tổng: {{ count($orders) }} đơn hàng | Từ {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</small>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Mã vận đơn</th>
                        <th>Người gửi</th>
                        <th>Người nhận</th>
                        <th>Địa chỉ</th>
                        <th>Tỉnh/TP</th>
                        <th>Kho gửi</th>
                        <th>Kho nhận</th>
                        <th>Tài xế</th>
                        <th>COD</th>
                        <th>COD đã thu</th>
                        <th>Phí VC</th>
                        <th>Doanh thu</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Ghi chú</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td>
                            <strong>{{ $order->tracking_number }}</strong>
                            @if($order->customer)
                            <br><small class="text-muted">KH: {{ $order->customer->name ?? 'N/A' }}</small>
                            @endif
                        </td>
                        <td>
                            {{ $order->sender_name }}<br>
                            <small class="text-muted">{{ $order->sender_phone }}</small>
                            @if($order->sender_address)
                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($order->sender_address, 30) }}</small>
                            @endif
                        </td>
                        <td>
                            {{ $order->receiver_name }}<br>
                            <small class="text-muted">{{ $order->receiver_phone }}</small>
                        </td>
                        <td>
                            {{ $order->receiver_address }}
                            @if($order->receiver_district)
                            <br><small class="text-muted">{{ $order->receiver_district }}</small>
                            @endif
                        </td>
                        <td><span class="badge bg-info">{{ $order->receiver_province }}</span></td>
                        <td>
                            @if($order->warehouse)
                            <span class="badge bg-primary">{{ $order->warehouse->name }}</span>
                            @if($order->warehouse->province)
                            <br><small class="text-muted">{{ $order->warehouse->province }}</small>
                            @endif
                            @else
                            <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($order->toWarehouse)
                            <span class="badge bg-warning text-dark">{{ $order->toWarehouse->name }}</span>
                            @if($order->toWarehouse->province)
                            <br><small class="text-muted">{{ $order->toWarehouse->province }}</small>
                            @endif
                            @else
                            <span class="text-muted">Giao trực tiếp</span>
                            @endif
                        </td>
                        <td>
                            @if($order->deliveryDriver)
                            <strong>{{ $order->deliveryDriver->name }}</strong><br>
                            <small class="text-muted">{{ $order->deliveryDriver->phone }}</small>
                            @if($order->deliveryDriver->driver_type)
                            <br><small class="badge bg-secondary">{{ $order->deliveryDriver->driver_type === 'shipper' ? 'Shipper' : 'Vận chuyển tỉnh' }}</small>
                            @endif
                            @else
                            <span class="text-muted">Chưa phân công</span>
                            @endif
                        </td>
                        <td><strong>{{ number_format($order->cod_amount) }} đ</strong></td>
                        <td>
                            @if($order->cod_collected && $order->cod_collected > 0)
                            <strong class="text-success">{{ number_format($order->cod_collected) }} đ</strong>
                            @else
                            <span class="text-muted">0 đ</span>
                            @endif
                        </td>
                        <td>
                            @if($order->return_fee && $order->return_fee > 0)
                            <strong class="text-warning">Phí trả hàng: {{ number_format($order->return_fee) }} đ</strong>
                            @elseif($order->shipping_fee && $order->shipping_fee > 0)
                            <strong class="text-info">{{ number_format($order->shipping_fee) }} đ</strong>
                            @else
                            <span class="text-muted">0 đ</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $revenue = ($order->cod_collected ?? 0) + ($order->shipping_fee ?? 0) + ($order->return_fee ?? 0);
                            @endphp
                            @if($revenue > 0)
                            <strong class="text-success">{{ number_format($revenue) }} đ</strong>
                            @else
                            <span class="text-muted">0 đ</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $isReturnOrder = false;
                                $hasCancelledStatus = false;
                                
                                if ($order->return_fee && $order->return_fee > 0) {
                                    $isReturnOrder = true;
                                }
                                
                                if ($order->statuses && $order->statuses->isNotEmpty()) {
                                    $hasCancelledStatus = $order->statuses->where('status', 'cancelled')->isNotEmpty();
                                }
                                
                                if (!$isReturnOrder && !$hasCancelledStatus && $order->warehouseTransactions) {
                                    $returnTransaction = $order->warehouseTransactions->first(function($trans) {
                                        return stripos($trans->notes ?? '', 'Trả đơn hàng') !== false || 
                                               stripos($trans->notes ?? '', 'Quay lại kho') !== false ||
                                               stripos($trans->notes ?? '', 'Đơn hàng bị hủy') !== false;
                                    });
                                    if ($returnTransaction) {
                                        $isReturnOrder = true;
                                    }
                                }
                            @endphp
                            @if($order->status === 'delivered')
                                @if($isReturnOrder || $hasCancelledStatus)
                                <span class="badge bg-warning text-dark me-1">Đã hủy</span>
                                <span class="badge bg-success">Đã giao (trả hàng)</span>
                                @else
                                <span class="badge bg-success">Đã giao</span>
                                @endif
                            @elseif($order->status === 'failed')
                            <span class="badge bg-danger">Thất bại</span>
                            @if($order->failure_reason)
                            <br><small class="text-muted" title="{{ $order->failure_reason }}">{{ \Illuminate\Support\Str::limit($order->failure_reason, 30) }}</small>
                            @endif
                            @elseif($order->status === 'cancelled')
                            <span class="badge bg-warning text-dark">Đã hủy</span>
                            @if($order->failure_reason)
                            <br><small class="text-muted" title="{{ $order->failure_reason }}">{{ \Illuminate\Support\Str::limit($order->failure_reason, 30) }}</small>
                            @endif
                            @else
                            <span class="badge bg-secondary">{{ $order->status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($order->created_at)
                            <small>{{ $order->created_at->format('d/m/Y H:i') }}</small>
                            @else
                            <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($order->delivery_notes)
                            <small class="text-muted" title="{{ $order->delivery_notes }}">{{ \Illuminate\Support\Str::limit($order->delivery_notes, 40) }}</small>
                            @elseif($order->notes)
                            <small class="text-muted" title="{{ $order->notes }}">{{ \Illuminate\Support\Str::limit($order->notes, 40) }}</small>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-primary" title="Xem chi tiết đầy đủ">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="16" class="text-center text-muted">
                            <i class="fas fa-inbox me-2"></i>Không có đơn hàng nào
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
