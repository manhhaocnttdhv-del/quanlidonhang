@extends('admin.layout')

@section('title', 'Chi Tiết Vận Đơn')
@section('page-title', 'Chi Tiết Vận Đơn')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Mã vận đơn: <strong>{{ $order->tracking_number }}</strong></h5>
                <span class="badge bg-{{ $order->status === 'delivered' ? 'success' : ($order->status === 'failed' ? 'danger' : 'warning') }} fs-6">
                    {{ $order->status }}
                </span>
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
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">Cập nhật trạng thái</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.orders.update-status', $order->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select" required>
                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                            <option value="pickup_pending" {{ $order->status === 'pickup_pending' ? 'selected' : '' }}>Chờ lấy hàng</option>
                            <option value="picking_up" {{ $order->status === 'picking_up' ? 'selected' : '' }}>Đang lấy hàng</option>
                            <option value="picked_up" {{ $order->status === 'picked_up' ? 'selected' : '' }}>Đã lấy hàng</option>
                            <option value="in_warehouse" {{ $order->status === 'in_warehouse' ? 'selected' : '' }}>Đã nhập kho</option>
                            <option value="in_transit" {{ $order->status === 'in_transit' ? 'selected' : '' }}>Đang vận chuyển</option>
                            <option value="out_for_delivery" {{ $order->status === 'out_for_delivery' ? 'selected' : '' }}>Đang giao hàng</option>
                            <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Đã giao hàng</option>
                            <option value="failed" {{ $order->status === 'failed' ? 'selected' : '' }}>Giao hàng thất bại</option>
                            <option value="returned" {{ $order->status === 'returned' ? 'selected' : '' }}>Đã hoàn</option>
                        </select>
                        <small class="text-muted">Trạng thái hiện tại: <strong>{{ $order->status }}</strong></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi chú</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Cập nhật</button>
                </form>
            </div>
        </div>
        
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
            </div>
        </div>
    </div>
</div>
@endsection

