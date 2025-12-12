@extends('admin.layout')

@section('title', 'Chi Tiết Bảng Kê')
@section('page-title', 'Chi Tiết Bảng Kê: ' . $reconciliation->reconciliation_number)

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Thông tin bảng kê</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Số bảng kê:</strong> {{ $reconciliation->reconciliation_number }}</p>
                        <p><strong>Khách hàng:</strong> {{ $reconciliation->customer->name ?? 'N/A' }}</p>
                        <p><strong>Khoảng thời gian:</strong> 
                            {{ $reconciliation->from_date->format('d/m/Y') }} - {{ $reconciliation->to_date->format('d/m/Y') }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Tổng COD:</strong> {{ number_format($reconciliation->total_cod_amount) }} đ</p>
                        <p><strong>Tổng phí vận chuyển:</strong> {{ number_format($reconciliation->total_shipping_fee) }} đ</p>
                        <p><strong>Tổng tiền:</strong> <span class="h5 text-primary">{{ number_format($reconciliation->total_amount) }} đ</span></p>
                        <p><strong>Đã thanh toán:</strong> {{ number_format($reconciliation->paid_amount) }} đ</p>
                        <p><strong>Còn lại:</strong> <span class="h5 text-danger">{{ number_format($reconciliation->remaining_amount) }} đ</span></p>
                        <p><strong>Trạng thái:</strong> 
                            <span class="badge bg-{{ $reconciliation->status === 'paid' ? 'success' : ($reconciliation->status === 'partial' ? 'warning' : 'secondary') }}">
                                {{ $reconciliation->status === 'paid' ? 'Đã thanh toán' : ($reconciliation->status === 'partial' ? 'Thanh toán một phần' : 'Chờ thanh toán') }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Cập nhật thanh toán</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.cod-reconciliations.update-payment', $reconciliation->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Số tiền thanh toán (đ) <span class="text-danger">*</span></label>
                        <input type="number" name="paid_amount" class="form-control" step="0.01" min="0" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-money-bill me-2"></i>Cập nhật
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Chi tiết đơn hàng</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>Mã vận đơn</th>
                        <th>Người nhận</th>
                        <th>COD</th>
                        <th>Phí VC</th>
                        <th>Tổng</th>
                        <th>Ngày giao</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reconciliation->orders ?? [] as $order)
                    <tr>
                        <td><strong>{{ $order->tracking_number }}</strong></td>
                        <td>{{ $order->receiver_name }}</td>
                        <td>{{ number_format($order->pivot->cod_amount) }} đ</td>
                        <td>{{ number_format($order->pivot->shipping_fee) }} đ</td>
                        <td>{{ number_format($order->pivot->cod_amount + $order->pivot->shipping_fee) }} đ</td>
                        <td>{{ $order->delivered_at ? $order->delivered_at->format('d/m/Y') : 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">Không có đơn hàng nào</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

