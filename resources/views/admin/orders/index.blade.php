@extends('admin.layout')

@section('title', 'Quản Lý Vận Đơn')
@section('page-title', 'Quản Lý Vận Đơn')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="fas fa-box me-2"></i>Danh sách vận đơn</h4>
    <a href="{{ route('admin.orders.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Tạo đơn mới
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="mb-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="tracking_number" class="form-control" placeholder="Mã vận đơn" value="{{ request('tracking_number') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                        <option value="pickup_pending" {{ request('status') == 'pickup_pending' ? 'selected' : '' }}>Chờ lấy hàng</option>
                        <option value="in_transit" {{ request('status') == 'in_transit' ? 'selected' : '' }}>Đang vận chuyển</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Đã giao</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Thất bại</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Tìm kiếm
                    </button>
                </div>
            </div>
        </form>
        
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>Mã vận đơn</th>
                        <th>Người gửi</th>
                        <th>Người nhận</th>
                        <th>Trạng thái</th>
                        <th>COD</th>
                        <th>Phí vận chuyển</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders ?? [] as $order)
                    <tr>
                        <td><strong>{{ $order->tracking_number }}</strong></td>
                        <td>{{ $order->sender_name }}<br><small class="text-muted">{{ $order->sender_phone }}</small></td>
                        <td>{{ $order->receiver_name }}<br><small class="text-muted">{{ $order->receiver_phone }}</small></td>
                        <td>
                            @php
                                $isReturnOrder = $order->return_fee && $order->return_fee > 0;
                                $hasCancelledStatus = $order->statuses && $order->statuses->where('status', 'cancelled')->isNotEmpty();
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
                            @elseif($order->status === 'cancelled')
                            <span class="badge bg-warning text-dark">Đã hủy</span>
                            @else
                            <span class="badge bg-warning">{{ $order->status }}</span>
                            @endif
                        </td>
                        <td>{{ number_format($order->cod_amount) }} đ</td>
                        <td>{{ number_format($order->shipping_fee) }} đ</td>
                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-primary" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.orders.edit', $order->id) }}" class="btn btn-sm btn-warning" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if(!in_array($order->status, ['delivered', 'cancelled']))
                                <button type="button" class="btn btn-sm btn-danger" onclick="showCancelModal({{ $order->id }}, '{{ addslashes($order->tracking_number) }}')" title="Hủy đơn hàng">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                                @endif
                                @php
                                    $user = auth()->user();
                                    $canDelete = false;
                                    if ($order->status === 'pending') {
                                        if ($user->isSuperAdmin() || $user->role === 'admin') {
                                            $canDelete = true;
                                        } elseif ($user->isWarehouseAdmin() && $user->warehouse_id && $order->warehouse_id == $user->warehouse_id) {
                                            $canDelete = true;
                                        }
                                    }
                                @endphp
                                @if($canDelete)
                                <form action="{{ route('admin.orders.destroy', $order->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">Không có đơn hàng nào</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cancelOrderForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Hủy đơn hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Lưu ý:</strong> Đơn hàng <strong id="cancelTrackingNumber"></strong> sẽ được hủy và quay lại kho cũ. Hành động này không thể hoàn tác.
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

<script>
function showCancelModal(orderId, trackingNumber) {
    document.getElementById('cancelTrackingNumber').textContent = trackingNumber;
    document.getElementById('cancelOrderForm').action = '{{ route("admin.orders.cancel", ":id") }}'.replace(':id', orderId);
    const modal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
    modal.show();
}
</script>
@endsection

