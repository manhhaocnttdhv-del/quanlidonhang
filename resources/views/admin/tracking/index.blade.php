@extends('admin.layout')

@section('title', 'Tracking')
@section('page-title', 'Tracking Đơn Hàng')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-search-location me-2"></i>Tra cứu đơn hàng</h5>
    </div>
    <div class="card-body">
        <form id="trackingForm" class="mb-4">
            <div class="row">
                <div class="col-md-8">
                    <input type="text" name="tracking_number" class="form-control form-control-lg" placeholder="Nhập mã vận đơn" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-search me-2"></i>Tra cứu
                    </button>
                </div>
            </div>
        </form>
        
        <div id="trackingResult" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Kết quả tra cứu</h5>
                </div>
                <div class="card-body">
                    <div id="trackingContent"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#trackingForm').on('submit', function(e) {
        e.preventDefault();
        const trackingNumber = $('input[name="tracking_number"]').val();
        
        $.ajax({
            url: '{{ route("admin.tracking.track") }}',
            method: 'POST',
            data: {
                tracking_number: trackingNumber,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.order) {
                    displayTrackingInfo(response);
                } else {
                    alert(response.message || 'Không tìm thấy đơn hàng');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Có lỗi xảy ra khi tra cứu';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert(errorMsg);
            }
        });
    });
    
    function displayTrackingInfo(data) {
        const order = data.order;
        const statusMap = {
            'pending': { text: 'Chờ xử lý', class: 'secondary' },
            'pickup_pending': { text: 'Chờ lấy hàng', class: 'warning' },
            'picking_up': { text: 'Đang lấy hàng', class: 'info' },
            'picked_up': { text: 'Đã lấy hàng', class: 'primary' },
            'in_warehouse': { text: 'Đã nhập kho', class: 'info' },
            'in_transit': { text: 'Đang vận chuyển', class: 'primary' },
            'out_for_delivery': { text: 'Đang giao hàng', class: 'warning' },
            'delivered': { text: 'Đã giao hàng', class: 'success' },
            'failed': { text: 'Giao hàng thất bại', class: 'danger' },
            'returned': { text: 'Đã hoàn', class: 'secondary' }
        };
        
        const currentStatus = statusMap[order.status] || { text: order.status, class: 'secondary' };
        
        let html = `
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Thông tin đơn hàng</h6>
                    <p><strong>Mã vận đơn:</strong> <span class="text-primary">${order.tracking_number}</span></p>
                    <p><strong>Người gửi:</strong> ${order.sender_name}</p>
                    <p><strong>SĐT gửi:</strong> ${order.sender_phone || 'N/A'}</p>
                    <p><strong>Địa chỉ gửi:</strong> ${order.sender_address || 'N/A'}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Thông tin nhận</h6>
                    <p><strong>Người nhận:</strong> ${order.receiver_name}</p>
                    <p><strong>SĐT nhận:</strong> ${order.receiver_phone || 'N/A'}</p>
                    <p><strong>Địa chỉ nhận:</strong> ${order.receiver_address || 'N/A'}</p>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <p class="mb-1 text-muted">Trạng thái hiện tại</p>
                                    <h5><span class="badge bg-${currentStatus.class}">${currentStatus.text}</span></h5>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-1 text-muted">COD</p>
                                    <h5>${new Intl.NumberFormat('vi-VN').format(order.cod_amount || 0)} đ</h5>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-1 text-muted">Phí vận chuyển</p>
                                    <h5>${new Intl.NumberFormat('vi-VN').format(order.shipping_fee || 0)} đ</h5>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-1 text-muted">Trọng lượng</p>
                                    <h5>${order.weight || 0} kg</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr>
            <h6 class="mb-3"><i class="fas fa-history me-2"></i>Lịch sử trạng thái</h6>
            <div class="timeline">
        `;
        
        if (order.statuses && order.statuses.length > 0) {
            order.statuses.forEach(function(status, index) {
                const statusInfo = statusMap[status.status] || { text: status.status, class: 'secondary' };
                const isLast = index === order.statuses.length - 1;
                html += `
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-${statusInfo.class} text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-${isLast ? 'check-circle' : 'circle'}"></i>
                            </div>
                            ${!isLast ? '<div class="bg-' + statusInfo.class + '" style="width: 2px; height: 50px; margin: 0 auto; opacity: 0.3;"></div>' : ''}
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">
                                <span class="badge bg-${statusInfo.class}">${statusInfo.text}</span>
                            </h6>
                            <p class="text-muted mb-1">${status.notes || 'Không có ghi chú'}</p>
                            ${status.location ? '<p class="text-muted mb-1"><i class="fas fa-map-marker-alt me-1"></i>' + status.location + '</p>' : ''}
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>${new Date(status.created_at).toLocaleString('vi-VN', { 
                                    year: 'numeric', 
                                    month: '2-digit', 
                                    day: '2-digit', 
                                    hour: '2-digit', 
                                    minute: '2-digit' 
                                })}
                            </small>
                        </div>
                    </div>
                `;
            });
        } else {
            html += '<p class="text-muted">Chưa có lịch sử trạng thái</p>';
        }
        
        html += '</div>';
        $('#trackingContent').html(html);
        $('#trackingResult').show();
    }
});
</script>
@endpush

