@extends('admin.layout')

@section('title', 'Báo Cáo')
@section('page-title', 'Báo Cáo')

@section('content')
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Tổng đơn hàng</h6>
                <h3 class="mb-0">{{ $dailyStats['total_orders'] ?? 0 }}</h3>
                <small>Hôm nay</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Đã giao</h6>
                <h3 class="mb-0">{{ $dailyStats['delivered_orders'] ?? 0 }}</h3>
                <small>Hôm nay</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Thất bại</h6>
                <h3 class="mb-0">{{ $dailyStats['failed_orders'] ?? 0 }}</h3>
                <small>Hôm nay</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Doanh thu</h6>
                <h3 class="mb-0">{{ number_format($dailyStats['total_revenue'] ?? 0) }} đ</h3>
                <small>Hôm nay</small>
                <small class="d-block mt-1" style="font-size: 0.75rem; opacity: 0.9;">
                    <i class="fas fa-info-circle"></i> COD đã thu + Phí vận chuyển
                </small>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Lọc báo cáo</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Từ ngày</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from', date('Y-m-d', strtotime('-30 days'))) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Đến ngày</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to', date('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Loại báo cáo</label>
                <select name="report_type" class="form-select">
                    <option value="daily" {{ request('report_type') == 'daily' ? 'selected' : '' }}>Báo cáo ngày</option>
                    <option value="monthly" {{ request('report_type') == 'monthly' ? 'selected' : '' }}>Báo cáo tháng</option>
                    <option value="revenue" {{ request('report_type') == 'revenue' ? 'selected' : '' }}>Doanh thu</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Xem báo cáo
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Chi tiết báo cáo</h5>
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            <strong>Doanh thu</strong> = COD đã thu (cod_collected) + Phí vận chuyển (shipping_fee) - Chỉ tính đơn hàng đã giao thành công. 
            <strong>COD đã thu</strong> = Tổng tiền COD đã thu thực tế từ khách hàng (cod_collected). 
            <strong>Tổng COD</strong> = Tổng tiền thu hộ cần thu (cod_amount) của tất cả đơn hàng.
        </small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Số đơn</th>
                        <th>Đã giao</th>
                        <th>Thất bại</th>
                        <th>Doanh thu</th>
                        <th>COD đã thu</th>
                        <th>Phí VC</th>
                        <th>Tổng COD</th>
                        <th>Tổng (Doanh thu + COD chưa thu)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData ?? [] as $row)
                    <tr>
                        <td>{{ $row->date ?? $row->created_at ?? 'N/A' }}</td>
                        <td>{{ $row->order_count ?? $row->total_orders ?? 0 }}</td>
                        <td>{{ $row->delivered_orders ?? 0 }}</td>
                        <td>{{ $row->failed_orders ?? 0 }}</td>
                        <td>
                            <strong class="text-success">{{ number_format($row->total_revenue ?? 0) }} đ</strong>
                            <br><small class="text-muted">COD đã thu + Phí VC</small>
                        </td>
                        <td>
                            <strong>{{ number_format($row->cod_collected ?? 0) }} đ</strong>
                            <br><small class="text-muted">COD đã thu</small>
                        </td>
                        <td>
                            <strong>{{ number_format($row->shipping_fee ?? 0) }} đ</strong>
                            <br><small class="text-muted">Phí vận chuyển</small>
                        </td>
                        <td>
                            <strong>{{ number_format($row->cod_amount ?? 0) }} đ</strong>
                            <br><small class="text-muted">COD cần thu</small>
                        </td>
                        <td>
                            <strong class="text-info">{{ number_format(($row->total_revenue ?? 0) + (($row->cod_amount ?? 0) - ($row->cod_collected ?? 0))) }} đ</strong>
                            <br><small class="text-muted">Tổng cộng</small>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">Không có dữ liệu</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

