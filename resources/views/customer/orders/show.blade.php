@extends('customer.layout')

@section('title', 'Chi Tiết Đơn Hàng')
@section('page-title', 'Chi Tiết Đơn Hàng')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Mã vận đơn: <strong>{{ $order->tracking_number }}</strong></h5>
                <div>
                    @php
                        $statusLabels = [
                            'pending' => ['label' => 'Chờ xử lý', 'class' => 'bg-secondary'],
                            'pickup_pending' => ['label' => 'Chờ lấy hàng', 'class' => 'bg-warning text-dark'],
                            'picking_up' => ['label' => 'Đang lấy hàng', 'class' => 'bg-info'],
                            'picked_up' => ['label' => 'Đã lấy hàng', 'class' => 'bg-primary'],
                            'in_warehouse' => ['label' => 'Trong kho', 'class' => 'bg-info'],
                            'in_transit' => ['label' => 'Đang vận chuyển', 'class' => 'bg-primary'],
                            'delivered' => ['label' => 'Đã giao', 'class' => 'bg-success'],
                            'failed' => ['label' => 'Thất bại', 'class' => 'bg-danger'],
                            'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-warning text-dark'],
                        ];
                        $statusInfo = $statusLabels[$order->status] ?? ['label' => $order->status, 'class' => 'bg-secondary'];
                    @endphp
                    <span class="badge {{ $statusInfo['class'] }} fs-6">{{ $statusInfo['label'] }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Người gửi</h6>
                        <p>
                            <strong>{{ $order->sender_name }}</strong><br>
                            <i class="fas fa-phone me-2"></i>{{ $order->sender_phone }}<br>
                            <i class="fas fa-map-marker-alt me-2"></i>{{ $order->sender_address }}<br>
                            <small class="text-muted">{{ $order->sender_ward }}, {{ $order->sender_province }}</small>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Người nhận</h6>
                        <p>
                            <strong>{{ $order->receiver_name }}</strong><br>
                            <i class="fas fa-phone me-2"></i>{{ $order->receiver_phone }}<br>
                            <i class="fas fa-map-marker-alt me-2"></i>{{ $order->receiver_address }}<br>
                            <small class="text-muted">{{ $order->receiver_ward }}, {{ $order->receiver_province }}</small>
                        </p>
                    </div>
                </div>
                
                <hr>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Kho gửi</h6>
                        @if($order->warehouse)
                        <p>
                            <strong>{{ $order->warehouse->name }}</strong><br>
                            <small class="text-muted">{{ $order->warehouse->address }}<br>
                            {{ $order->warehouse->province }}</small>
                        </p>
                        @else
                        <p class="text-muted">Chưa xác định</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Kho đến</h6>
                        @if($order->toWarehouse)
                        <p>
                            <strong>{{ $order->toWarehouse->name }}</strong><br>
                            <small class="text-muted">{{ $order->toWarehouse->address }}<br>
                            {{ $order->toWarehouse->province }}</small>
                        </p>
                        @else
                        <p class="text-muted">Chưa xác định</p>
                        @endif
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Trọng lượng:</strong> {{ $order->weight }} kg</p>
                        <p><strong>Loại hàng:</strong> {{ $order->item_type ?? 'N/A' }}</p>
                        <p><strong>COD:</strong> {{ number_format($order->cod_amount ?? 0) }} đ</p>
                        @if($order->length && $order->width && $order->height)
                        <p><strong>Kích thước:</strong> {{ $order->length }} × {{ $order->width }} × {{ $order->height }} cm</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <p><strong>Phí vận chuyển:</strong> {{ number_format($order->shipping_fee ?? 0) }} đ</p>
                        <p><strong>Loại dịch vụ:</strong> 
                            @if($order->service_type == 'express')
                                Hỏa tốc
                            @elseif($order->service_type == 'economy')
                                Tiết kiệm
                            @else
                                Tiêu chuẩn
                            @endif
                        </p>
                        <p><strong>Hàng dễ vỡ:</strong> {{ $order->is_fragile ? 'Có' : 'Không' }}</p>
                        @if($order->notes)
                        <p><strong>Ghi chú:</strong> {{ $order->notes }}</p>
                        @endif
                    </div>
                </div>
                
                @if($order->pickupDriver || $order->deliveryDriver)
                <hr>
                <div class="row">
                    @if($order->pickupDriver)
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Tài xế lấy hàng</h6>
                        <p>
                            <strong>{{ $order->pickupDriver->name }}</strong><br>
                            <small class="text-muted">
                                <i class="fas fa-phone me-1"></i>{{ $order->pickupDriver->phone }}
                            </small>
                        </p>
                    </div>
                    @endif
                    @if($order->deliveryDriver)
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Tài xế giao hàng</h6>
                        <p>
                            <strong>{{ $order->deliveryDriver->name }}</strong><br>
                            <small class="text-muted">
                                <i class="fas fa-phone me-1"></i>{{ $order->deliveryDriver->phone }}
                            </small>
                        </p>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Lịch sử trạng thái</h5>
            </div>
            <div class="card-body">
                @if($order->statuses && $order->statuses->count() > 0)
                <div class="timeline">
                    @foreach($order->statuses as $status)
                    <div class="timeline-item mb-3 pb-3 border-bottom">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <div class="timeline-marker bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas fa-check text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">
                                    @php
                                        $statusInfo = $statusLabels[$status->status] ?? ['label' => $status->status, 'class' => 'bg-secondary'];
                                    @endphp
                                    <span class="badge {{ $statusInfo['class'] }} me-2">{{ $statusInfo['label'] }}</span>
                                </h6>
                                @if($status->notes)
                                <p class="mb-1">{{ $status->notes }}</p>
                                @endif
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>{{ $status->created_at->format('d/m/Y H:i:s') }}
                                </small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-muted text-center py-3">Chưa có lịch sử trạng thái</p>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Thông tin đơn hàng</h5>
            </div>
            <div class="card-body">
                <p><strong>Ngày tạo:</strong><br>
                <small class="text-muted">{{ $order->created_at->format('d/m/Y H:i:s') }}</small></p>
                
                @if($order->updated_at != $order->created_at)
                <p><strong>Ngày cập nhật:</strong><br>
                <small class="text-muted">{{ $order->updated_at->format('d/m/Y H:i:s') }}</small></p>
                @endif
                
                <hr>
                
                <h6 class="mb-3">Tổng thanh toán</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span>Phí vận chuyển:</span>
                    <strong>{{ number_format($order->shipping_fee ?? 0) }} đ</strong>
                </div>
                @if($order->cod_amount > 0)
                <div class="d-flex justify-content-between mb-2">
                    <span>COD:</span>
                    <strong>{{ number_format($order->cod_amount) }} đ</strong>
                </div>
                @endif
                <hr>
                <div class="d-flex justify-content-between">
                    <strong>Tổng cộng:</strong>
                    <strong class="text-primary">{{ number_format(($order->shipping_fee ?? 0) + ($order->cod_amount ?? 0)) }} đ</strong>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body text-center">
                <a href="{{ route('customer.orders.index') }}" class="btn btn-secondary w-100 mb-2">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại danh sách
                </a>
                <a href="{{ route('customer.orders.create') }}" class="btn btn-primary w-100">
                    <i class="fas fa-plus me-2"></i>Tạo đơn hàng mới
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-item:last-child {
    border-bottom: none !important;
    padding-bottom: 0 !important;
    margin-bottom: 0 !important;
}
</style>
@endsection

