@extends('admin.layout')

@section('title', 'Chi Tiết Vận Đơn')
@section('page-title', 'Chi Tiết Vận Đơn')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Mã vận đơn: <strong>{{ $order->tracking_number }}</strong></h5>
                <div>
                    @php
                        $isReturnOrder = $order->return_fee && $order->return_fee > 0;
                        $hasCancelledStatus = $order->statuses && $order->statuses->where('status', 'cancelled')->isNotEmpty();
                    @endphp
                    @if($order->status === 'delivered')
                        @if($isReturnOrder || $hasCancelledStatus)
                        <span class="badge bg-warning text-dark me-1 fs-6">Đã hủy</span>
                        <span class="badge bg-success fs-6">Đã giao (trả hàng)</span>
                        @else
                        <span class="badge bg-success fs-6">Đã giao</span>
                        @endif
                    @elseif($order->status === 'failed')
                    <span class="badge bg-danger fs-6">Thất bại</span>
                    @elseif($order->status === 'cancelled')
                    <span class="badge bg-warning text-dark fs-6">Đã hủy</span>
                    @else
                    <span class="badge bg-warning fs-6">{{ $order->status }}</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Người gửi</h6>
                        <p><strong>{{ $order->sender_name }}</strong><br>
                        {{ $order->sender_phone }}<br>
                        {{ $order->sender_address }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Người nhận</h6>
                        <p><strong>{{ $order->receiver_name }}</strong><br>
                        {{ $order->receiver_phone }}<br>
                        {{ $order->receiver_address }}</p>
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
                        <p><strong>COD:</strong> {{ number_format($order->cod_amount) }} đ</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Phí vận chuyển:</strong> {{ number_format($order->shipping_fee) }} đ</p>
                        <p><strong>Loại dịch vụ:</strong> {{ $order->service_type }}</p>
                        <p><strong>Hàng dễ vỡ:</strong> {{ $order->is_fragile ? 'Có' : 'Không' }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Lịch sử trạng thái</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @foreach($order->statuses ?? [] as $status)
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">{{ $status->status }}</h6>
                            <p class="text-muted mb-1">{{ $status->notes }}</p>
                            <small class="text-muted">{{ $status->created_at->format('d/m/Y H:i:s') }}</small>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Thông tin khác</h6>
            </div>
            <div class="card-body">
                <p><strong>Ngày tạo:</strong><br>{{ $order->created_at->format('d/m/Y H:i:s') }}</p>
                @if($order->picked_up_at)
                <p><strong>Ngày lấy hàng:</strong><br>{{ $order->picked_up_at->format('d/m/Y H:i:s') }}</p>
                @endif
                @if($order->delivered_at)
                <p><strong>Ngày giao hàng:</strong><br>{{ $order->delivered_at->format('d/m/Y H:i:s') }}</p>
                @endif
                @if($order->status === 'cancelled' && $order->failure_reason)
                <p><strong>Lý do hủy:</strong><br>{{ $order->failure_reason }}</p>
                @endif
            </div>
        </div>

        @if(!in_array($order->status, ['delivered', 'cancelled']))
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Thao tác</h6>
            </div>
            <div class="card-body">
                <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                    <i class="fas fa-times-circle me-2"></i>Hủy đơn hàng
                </button>
            </div>
        </div>
        @endif
    </div>
</div>

@if(!in_array($order->status, ['delivered', 'cancelled']))
<div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.orders.cancel', $order->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Hủy đơn hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Lưu ý:</strong> Đơn hàng sẽ được hủy và quay lại kho cũ. Hành động này không thể hoàn tác.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lý do hủy <span class="text-danger">*</span></label>
                        <textarea name="cancellation_reason" class="form-control" rows="4" required placeholder="Nhập lý do hủy đơn hàng..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi chú thêm</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Ghi chú thêm (nếu có)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-danger">Xác nhận hủy đơn hàng</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

