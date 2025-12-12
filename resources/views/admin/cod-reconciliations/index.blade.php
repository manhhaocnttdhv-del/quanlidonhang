@extends('admin.layout')

@section('title', 'Bảng Kê COD')
@section('page-title', 'Bảng Kê COD')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="fas fa-file-invoice-dollar me-2"></i>Danh sách bảng kê</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createReconciliationModal">
        <i class="fas fa-plus me-2"></i>Tạo bảng kê mới
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>Số bảng kê</th>
                        <th>Khách hàng</th>
                        <th>Từ ngày</th>
                        <th>Đến ngày</th>
                        <th>Tổng COD</th>
                        <th>Tổng phí VC</th>
                        <th>Tổng tiền</th>
                        <th>Đã thanh toán</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reconciliations ?? [] as $reconciliation)
                    <tr>
                        <td><strong>{{ $reconciliation->reconciliation_number }}</strong></td>
                        <td>{{ $reconciliation->customer->name ?? 'N/A' }}</td>
                        <td>{{ $reconciliation->from_date->format('d/m/Y') }}</td>
                        <td>{{ $reconciliation->to_date->format('d/m/Y') }}</td>
                        <td>{{ number_format($reconciliation->total_cod_amount) }} đ</td>
                        <td>{{ number_format($reconciliation->total_shipping_fee) }} đ</td>
                        <td><strong>{{ number_format($reconciliation->total_amount) }} đ</strong></td>
                        <td>{{ number_format($reconciliation->paid_amount) }} đ</td>
                        <td>
                            <span class="badge bg-{{ $reconciliation->status === 'paid' ? 'success' : ($reconciliation->status === 'partial' ? 'warning' : 'secondary') }}">
                                {{ $reconciliation->status === 'paid' ? 'Đã thanh toán' : ($reconciliation->status === 'partial' ? 'Thanh toán một phần' : 'Chờ thanh toán') }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.cod-reconciliations.show', $reconciliation->id) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center">Chưa có bảng kê nào</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Reconciliation Modal -->
<div class="modal fade" id="createReconciliationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.cod-reconciliations.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tạo bảng kê mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Khách hàng</label>
                            <select name="customer_id" class="form-select">
                                <option value="">Tất cả khách hàng</option>
                                @foreach($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Từ ngày <span class="text-danger">*</span></label>
                            <input type="date" name="from_date" class="form-control" required value="{{ date('Y-m-d', strtotime('-7 days')) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Đến ngày <span class="text-danger">*</span></label>
                            <input type="date" name="to_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Hệ thống sẽ tự động tìm các đơn hàng đã giao trong khoảng thời gian này để tạo bảng kê.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Tạo bảng kê</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

