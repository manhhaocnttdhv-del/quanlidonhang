@extends('customer.layout')

@section('title', 'Danh sách đơn hàng')
@section('page-title', 'Danh sách đơn hàng')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="fas fa-box me-2"></i>Danh sách đơn hàng</h4>
    <a href="{{ route('customer.orders.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Tạo đơn mới
    </a>
</div>

<div class="card">
    <div class="card-body">
                <div class="card-body">
                    <form method="GET" action="{{ route('customer.orders.index') }}" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <input type="text" name="tracking_number" class="form-control" placeholder="Mã vận đơn" value="{{ request('tracking_number') }}">
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                                    <option value="pickup_pending" {{ request('status') == 'pickup_pending' ? 'selected' : '' }}>Chờ lấy hàng</option>
                                    <option value="picking_up" {{ request('status') == 'picking_up' ? 'selected' : '' }}>Đang lấy hàng</option>
                                    <option value="picked_up" {{ request('status') == 'picked_up' ? 'selected' : '' }}>Đã lấy hàng</option>
                                    <option value="in_warehouse" {{ request('status') == 'in_warehouse' ? 'selected' : '' }}>Trong kho</option>
                                    <option value="in_transit" {{ request('status') == 'in_transit' ? 'selected' : '' }}>Đang vận chuyển</option>
                                    <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Đã giao</option>
                                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Thất bại</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="Từ ngày">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="Đến ngày">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Tìm kiếm
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã vận đơn</th>
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
                                    <td>
                                        {{ $order->receiver_name }}<br>
                                        <small class="text-muted">{{ $order->receiver_phone }}</small><br>
                                        <small class="text-muted">{{ $order->receiver_address }}, {{ $order->receiver_ward }}, {{ $order->receiver_province }}</small>
                                    </td>
                                    <td>
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
                                        <span class="badge status-badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                                    </td>
                                    <td>{{ number_format($order->cod_amount ?? 0) }} đ</td>
                                    <td>{{ number_format($order->shipping_fee ?? 0) }} đ</td>
                                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('customer.orders.show', $order->id) }}" class="btn btn-sm btn-primary" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Chưa có đơn hàng nào</p>
                                        <a href="{{ route('customer.orders.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Tạo đơn hàng đầu tiên
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($orders && $orders->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $orders->links() }}
                    </div>
                    @endif
    </div>
</div>
@endsection

