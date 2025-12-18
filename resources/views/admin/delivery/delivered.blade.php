@extends('admin.layout')

@section('title', 'Đơn Hàng Đã Giao Thành Công')
@section('page-title', 'Đơn Hàng Đã Giao Thành Công')

@section('content')
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Đơn Hàng Đã Giao Thành Công - Kho Nhận</h5>
                <small>Danh sách đơn hàng đã được nhận vào kho và giao thành công đến khách hàng</small>
            </div>
            <div class="card-body">
                <!-- Filter Form -->
                <form method="GET" action="{{ route('admin.delivery.delivered') }}" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Từ ngày</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Đến ngày</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mã vận đơn</label>
                            <input type="text" name="tracking_number" class="form-control" value="{{ request('tracking_number') }}" placeholder="Nhập mã vận đơn...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tài xế</label>
                            <select name="driver_id" class="form-select">
                                <option value="">Tất cả tài xế</option>
                                @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Tìm kiếm
                            </button>
                            <a href="{{ route('admin.delivery.delivered') }}" class="btn btn-secondary">
                                <i class="fas fa-redo me-1"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2">Tổng Doanh Thu</h6>
                                <h3 class="mb-0">{{ number_format($totalRevenue, 0, ',', '.') }} đ</h3>
                                <small>COD đã thu + Phí vận chuyển</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2">COD Đã Thu</h6>
                                <h3 class="mb-0">{{ number_format($totalCodCollected, 0, ',', '.') }} đ</h3>
                                <small>Tổng tiền COD đã thu</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2">Phí Vận Chuyển</h6>
                                <h3 class="mb-0">{{ number_format($totalShippingFee, 0, ',', '.') }} đ</h3>
                                <small>Tổng phí vận chuyển</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Mã vận đơn</th>
                                <th>Kho gửi</th>
                                <th>Kho nhận</th>
                                <th>Người nhận</th>
                                <th>Địa chỉ giao</th>
                                <th>Tỉnh/TP</th>
                                <th>Tài xế</th>
                                <th>COD</th>
                                <th>Phí VC</th>
                                <th>Doanh thu</th>
                                <th>Ngày giao</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deliveredOrders as $order)
                            <tr>
                                <td>
                                    <strong>{{ $order->tracking_number }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $order->id }}</small>
                                </td>
                                <td>
                                    @if($order->from_warehouse)
                                        <strong>{{ $order->from_warehouse->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $order->from_warehouse->province }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->to_warehouse_from_transaction)
                                        <strong class="text-success">{{ $order->to_warehouse_from_transaction->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $order->to_warehouse_from_transaction->province }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->customer)
                                        <strong>{{ $order->customer->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $order->customer->phone }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $order->receiver_address ?? '-' }}
                                </td>
                                <td>
                                    {{ $order->receiver_province ?? '-' }}
                                </td>
                                <td>
                                    @if($order->deliveryDriver)
                                        <strong>{{ $order->deliveryDriver->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $order->deliveryDriver->phone }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <strong>{{ number_format($order->cod_collected ?? $order->cod_amount ?? 0, 0, ',', '.') }} đ</strong>
                                </td>
                                <td class="text-end">
                                    {{ number_format($order->shipping_fee ?? 0, 0, ',', '.') }} đ
                                </td>
                                <td class="text-end">
                                    <strong class="text-success">{{ number_format($order->revenue, 0, ',', '.') }} đ</strong>
                                </td>
                                <td>
                                    @if($order->delivered_at)
                                        {{ \Carbon\Carbon::parse($order->delivered_at)->format('d/m/Y H:i') }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-info" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="12" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Không có đơn hàng nào</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $deliveredOrders->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
