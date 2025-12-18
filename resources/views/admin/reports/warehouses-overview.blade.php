@extends('admin.layout')

@section('title', 'Báo Cáo Tổng Hợp Kho')
@section('page-title', 'Báo Cáo Tổng Hợp Tất Cả Kho')

@section('content')
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Lọc báo cáo</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.warehouses-overview') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Từ ngày</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from', date('Y-m-d', strtotime('-30 days'))) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Đến ngày</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to', date('Y-m-d')) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Xem báo cáo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tổng hợp -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary" style="cursor: pointer;" onclick="toggleWarehouseDetails()">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Tổng số kho</h6>
                <h3 class="mb-0">
                    <a href="javascript:void(0)" class="text-white text-decoration-none" onclick="event.stopPropagation(); toggleWarehouseDetails();">
                        {{ $totalStats['total_warehouses'] ?? 0 }}
                        <i class="fas fa-chevron-down ms-2" id="warehouseToggleIcon"></i>
                    </a>
                </h3>
                <small>Click để xem chi tiết</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Tổng tồn kho</h6>
                <h3 class="mb-0">{{ $totalStats['total_current_inventory'] ?? 0 }}</h3>
                <small>Đơn hàng trong kho</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Đang đến kho</h6>
                <h3 class="mb-0">{{ $totalStats['total_incoming_orders'] ?? 0 }}</h3>
                <small>Đơn hàng đang vận chuyển</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Đã giao</h6>
                <h3 class="mb-0">{{ $totalStats['total_delivered'] ?? 0 }}</h3>
                <small>Trong kỳ báo cáo</small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Tổng doanh thu vận chuyển</h6>
                <h3 class="mb-0">{{ number_format($totalStats['total_shipping_revenue'] ?? 0) }} đ</h3>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Tổng COD</h6>
                <h3 class="mb-0">{{ number_format($totalStats['total_cod_amount'] ?? 0) }} đ</h3>
            </div>
        </div>
    </div>
</div>

<!-- Chi tiết từng kho -->
<div class="card" id="warehouseDetailsCard" style="display: none;">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Chi tiết từng kho</h5>
        <small class="text-muted">Tổng: {{ count($warehouseStats ?? []) }} kho</small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Kho</th>
                        <th>Admin kho</th>
                        <th>Tồn kho</th>
                        <th>Đang đến</th>
                        <th>Tổng đơn</th>
                        <th>Đã giao</th>
                        <th>Doanh thu VC</th>
                        <th>Tổng COD</th>
                        <th>Tài xế</th>
                        <th>Nhập/Xuất</th>
                        <th>Vận chuyển</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($warehouseStats ?? [] as $stat)
                    <tr>
                        <td>
                            <strong>{{ $stat['warehouse']->name }}</strong><br>
                            <small class="text-muted">{{ $stat['warehouse']->province }}</small>
                        </td>
                        <td>
                            @if(isset($stat['warehouse_admins']) && $stat['warehouse_admins']->count() > 0)
                                @foreach($stat['warehouse_admins'] as $admin)
                                    <div class="mb-1">
                                        <strong>{{ $admin->name }}</strong><br>
                                        <small class="text-muted">{{ $admin->email }}</small>
                                    </div>
                                @endforeach
                            @else
                                <span class="text-muted">Chưa có admin</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $stat['current_inventory'] }}</span>
                        </td>
                        <td>
                            @if($stat['incoming_orders'] > 0)
                                <span class="badge bg-warning">{{ $stat['incoming_orders'] }}</span>
                            @else
                                <span class="badge bg-secondary">0</span>
                            @endif
                        </td>
                        <td>{{ $stat['total_orders'] }}</td>
                        <td>
                            <span class="badge bg-success">{{ $stat['delivered_orders'] }}</span>
                        </td>
                        <td>
                            <div>
                                <strong class="text-primary">VC: {{ number_format($stat['total_shipping_revenue'] ?? 0) }} đ</strong>
                                @if(($stat['total_return_fee'] ?? 0) > 0)
                                <br><strong class="text-warning">Trả hàng: {{ number_format($stat['total_return_fee']) }} đ</strong>
                                @endif
                                <br><strong class="text-success">Tổng: {{ number_format($stat['total_revenue'] ?? 0) }} đ</strong>
                            </div>
                        </td>
                        <td>
                            <div>
                                <strong class="text-danger">COD: {{ number_format($stat['total_cod_amount'] ?? 0) }} đ</strong>
                                @if(($stat['total_cod_collected'] ?? 0) > 0)
                                <br><small class="text-success">Đã thu: {{ number_format($stat['total_cod_collected']) }} đ</small>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="small">
                                <div><strong>Tổng:</strong> {{ $stat['drivers_count'] ?? 0 }}</div>
                                <div><span class="badge bg-info">Shipper: {{ $stat['shippers_count'] ?? 0 }}</span></div>
                                <div><span class="badge bg-warning">Vận chuyển tỉnh: {{ $stat['intercity_drivers_count'] ?? 0 }}</span></div>
                            </div>
                        </td>
                        <td>
                            <div class="small">
                                <div>Nhập: <strong>{{ $stat['in_transactions'] }}</strong></div>
                                <div>Xuất: <strong>{{ $stat['out_transactions'] }}</strong></div>
                            </div>
                        </td>
                        <td>
                            <div class="small">
                                <div>Nhận: <span class="badge bg-primary">{{ $stat['received_from_other_warehouses'] }}</span></div>
                                <div>Gửi: <span class="badge bg-warning">{{ $stat['shipped_to_other_warehouses'] }}</span></div>
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('admin.reports.warehouse-orders', $stat['warehouse']->id) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> Xem
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center">Không có dữ liệu</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleWarehouseDetails() {
    const card = document.getElementById('warehouseDetailsCard');
    const icon = document.getElementById('warehouseToggleIcon');
    
    if (card.style.display === 'none') {
        card.style.display = 'block';
        if (icon) {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        }
    } else {
        card.style.display = 'none';
        if (icon) {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    }
}
</script>
@endsection

