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
                            <span class="badge bg-{{ $order->status === 'delivered' ? 'success' : ($order->status === 'failed' ? 'danger' : 'warning') }}">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td>{{ number_format($order->cod_amount) }} đ</td>
                        <td>{{ number_format($order->shipping_fee) }} đ</td>
                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-primary" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.orders.edit', $order->id) }}" class="btn btn-sm btn-warning" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            @php
                                $user = auth()->user();
                                $canDelete = false;
                                if ($order->status === 'pending') {
                                    // Super admin/Admin có thể xóa bất kỳ đơn hàng pending nào
                                    if ($user->isSuperAdmin() || $user->role === 'admin') {
                                        $canDelete = true;
                                    }
                                    // Warehouse admin chỉ xóa được đơn hàng pending của kho mình
                                    elseif ($user->isWarehouseAdmin() && $user->warehouse_id && $order->warehouse_id == $user->warehouse_id) {
                                        $canDelete = true;
                                    }
                                }
                            @endphp
                            @if($canDelete)
                            <form action="{{ route('admin.orders.destroy', $order->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
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
@endsection

