@extends('customer.layout')

@section('title', 'Trang chủ')
@section('page-title', 'Trang chủ')

@section('content')
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Tổng đơn hàng</h6>
                        <h3 class="mb-0">{{ $totalOrders ?? 0 }}</h3>
                    </div>
                    <i class="fas fa-box fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Đã giao</h6>
                        <h3 class="mb-0">{{ $deliveredOrders ?? 0 }}</h3>
                    </div>
                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Đang xử lý</h6>
                        <h3 class="mb-0">{{ $pendingOrders ?? 0 }}</h3>
                    </div>
                    <i class="fas fa-spinner fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Đơn hàng gần đây</h5>
                <a href="{{ route('customer.orders.index') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-eye me-1"></i>Xem tất cả
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Mã vận đơn</th>
                                <th>Người nhận</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders ?? [] as $order)
                            <tr>
                                <td><strong>{{ $order->tracking_number }}</strong></td>
                                <td>
                                    {{ $order->receiver_name }}<br>
                                    <small class="text-muted">{{ $order->receiver_phone }}</small>
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
                                    <span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                                </td>
                                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('customer.orders.show', $order->id) }}" class="btn btn-sm btn-primary" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
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
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Thông tin tài khoản</h5>
            </div>
            <div class="card-body">
                <p><strong>Họ và tên:</strong><br>{{ $customer->name }}</p>
                <p><strong>Số điện thoại:</strong><br>{{ $customer->phone }}</p>
                @if($customer->email)
                <p><strong>Email:</strong><br>{{ $customer->email }}</p>
                @endif
                <p><strong>Mã khách hàng:</strong><br><code>{{ $customer->code }}</code></p>
                @if($customer->address)
                <p><strong>Địa chỉ:</strong><br>{{ $customer->address }}, {{ $customer->ward }}, {{ $customer->province }}</p>
                @endif
            </div>
        </div>
        
        <div class="card">
            <div class="card-body text-center">
                <a href="{{ route('customer.orders.create') }}" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-plus-circle me-2"></i>Tạo đơn hàng mới
                </a>
                <a href="{{ route('customer.orders.index') }}" class="btn btn-outline-primary w-100">
                    <i class="fas fa-list me-2"></i>Xem tất cả đơn hàng
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

