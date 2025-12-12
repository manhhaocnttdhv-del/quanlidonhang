@extends('admin.layout')

@section('title', 'Tra Cước')
@section('page-title', 'Tra Cước Vận Chuyển')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Tính phí vận chuyển</h5>
            </div>
            <div class="card-body">
                <form id="calculateForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tỉnh/Thành gửi <span class="text-danger">*</span></label>
                            <input type="text" name="from_province" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quận/Huyện gửi</label>
                            <input type="text" name="from_district" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tỉnh/Thành nhận <span class="text-danger">*</span></label>
                            <input type="text" name="to_province" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quận/Huyện nhận</label>
                            <input type="text" name="to_district" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Trọng lượng (kg) <span class="text-danger">*</span></label>
                        <input type="number" name="weight" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Loại dịch vụ <span class="text-danger">*</span></label>
                        <select name="service_type" class="form-select" required>
                            <option value="standard">Tiêu chuẩn</option>
                            <option value="express">Hỏa tốc</option>
                            <option value="economy">Tiết kiệm</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tiền thu hộ COD (đ)</label>
                        <input type="number" name="cod_amount" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-calculator me-2"></i>Tính phí
                    </button>
                </form>
                
                <div id="feeResult" class="mt-4" style="display: none;">
                    <div class="alert alert-success">
                        <h6>Kết quả tính phí:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Phí cơ bản:</strong> <span id="baseFee">0</span> đ</p>
                                <p class="mb-1"><strong>Phí theo kg:</strong> <span id="weightFee">0</span> đ</p>
                                <p class="mb-1"><strong>Phí COD:</strong> <span id="codFee">0</span> đ</p>
                            </div>
                            <div class="col-md-6">
                                <h4 class="text-primary mb-0"><strong>Tổng phí:</strong> <span id="totalFee">0</span> đ</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Bảng cước hiện tại</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Từ</th>
                                <th>Đến</th>
                                <th>Loại DV</th>
                                <th>Phí cơ bản</th>
                                <th>Phí/kg</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shippingFees ?? [] as $fee)
                            <tr>
                                <td>{{ $fee->from_province }}</td>
                                <td>{{ $fee->to_province }}</td>
                                <td>{{ $fee->service_type }}</td>
                                <td>{{ number_format($fee->base_fee) }} đ</td>
                                <td>{{ number_format($fee->weight_fee_per_kg) }} đ</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">Chưa có bảng cước</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#calculateForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        $.ajax({
            url: '{{ route("admin.shipping-fees.calculate") }}',
            method: 'POST',
            data: formData + '&_token=' + $('meta[name="csrf-token"]').attr('content'),
            success: function(response) {
                if (response.total_fee) {
                    $('#baseFee').text(new Intl.NumberFormat('vi-VN').format(response.base_fee || 0));
                    $('#weightFee').text(new Intl.NumberFormat('vi-VN').format(response.weight_fee || 0));
                    $('#codFee').text(new Intl.NumberFormat('vi-VN').format(response.cod_fee || 0));
                    $('#totalFee').text(new Intl.NumberFormat('vi-VN').format(response.total_fee));
                    $('#feeResult').show();
                } else {
                    alert(response.message || 'Không tìm thấy bảng cước phù hợp');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Có lỗi xảy ra khi tính phí';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors).flat().join(', ');
                }
                alert(errorMsg);
            }
        });
    });
});
</script>
@endpush

