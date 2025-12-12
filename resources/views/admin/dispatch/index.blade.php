@extends('admin.layout')

@section('title', 'Điều Phối Nhận')
@section('page-title', 'Điều Phối Nhận')

@section('content')
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Đơn hàng chờ phân công tài xế</h5>
        <small class="text-muted">Chọn đơn hàng và phân công tài xế đến lấy hàng</small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAll">
                        </th>
                        <th>Mã vận đơn</th>
                        <th>Người gửi</th>
                        <th>Địa chỉ lấy hàng</th>
                        <th>Người nhận</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingOrders ?? [] as $order)
                    <tr>
                        <td>
                            <input type="checkbox" class="order-checkbox" value="{{ $order->id }}">
                        </td>
                        <td><strong>{{ $order->tracking_number }}</strong></td>
                        <td>
                            <strong>{{ $order->sender_name }}</strong><br>
                            <small class="text-muted"><i class="fas fa-phone me-1"></i>{{ $order->sender_phone }}</small>
                        </td>
                        <td>
                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                            {{ $order->sender_address }}<br>
                            <small class="text-muted">
                                {{ $order->sender_province }}
                                @if($order->sender_district), {{ $order->sender_district }}@endif
                                @if($order->sender_ward), {{ $order->sender_ward }}@endif
                            </small>
                        </td>
                        <td>
                            {{ $order->receiver_name }}<br>
                            <small class="text-muted">{{ $order->receiver_province }}</small>
                        </td>
                        <td>
                            <span class="badge bg-secondary">Chờ phân công</span>
                        </td>
                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-primary" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">Không có đơn hàng nào chờ phân công</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Đơn hàng đã phân công tài xế</h5>
        <small class="text-muted">Cập nhật trạng thái khi tài xế đang lấy hàng hoặc đã lấy hàng</small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Mã vận đơn</th>
                        <th>Tài xế</th>
                        <th>Người gửi</th>
                        <th>Địa chỉ lấy hàng</th>
                        <th>Trạng thái</th>
                        <th>Thời gian dự kiến</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignedOrders ?? [] as $order)
                    <tr>
                        <td><strong>{{ $order->tracking_number }}</strong></td>
                        <td>
                            <strong>{{ $order->pickupDriver->name ?? 'N/A' }}</strong><br>
                            <small class="text-muted"><i class="fas fa-phone me-1"></i>{{ $order->pickupDriver->phone ?? 'N/A' }}</small>
                        </td>
                        <td>
                            <strong>{{ $order->sender_name }}</strong><br>
                            <small class="text-muted"><i class="fas fa-phone me-1"></i>{{ $order->sender_phone }}</small>
                        </td>
                        <td>
                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                            {{ $order->sender_address }}<br>
                            <small class="text-muted">{{ $order->sender_province }}</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $order->status === 'picking_up' ? 'info' : 'warning' }}">
                                {{ $order->status === 'picking_up' ? 'Đang lấy hàng' : 'Đã phân công' }}
                            </span>
                        </td>
                        <td>
                            @if($order->pickup_scheduled_at)
                            {{ $order->pickup_scheduled_at->format('d/m/Y H:i') }}
                            @else
                            <span class="text-muted">Chưa có</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                @if($order->status === 'pickup_pending')
                                <button type="button" class="btn btn-sm btn-info" onclick="updatePickupStatus({{ $order->id }}, 'picking_up')" title="Tài xế đang đi lấy hàng">
                                    <i class="fas fa-walking"></i> Đang lấy
                                </button>
                                @endif
                                @if($order->status === 'picking_up')
                                <button type="button" class="btn btn-sm btn-success" onclick="updatePickupStatus({{ $order->id }}, 'picked_up')" title="Tài xế đã lấy hàng và đưa về kho">
                                    <i class="fas fa-check"></i> Đã lấy
                                </button>
                                @endif
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-primary" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">Không có đơn hàng nào đã được phân công</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Phân công tài xế lấy hàng</h5>
        <small class="text-muted">Tài xế sẽ đến địa chỉ người gửi (Nghệ An) để lấy hàng và đưa về kho</small>
    </div>
    <div class="card-body">
        <form id="assignDriverForm" action="{{ route('admin.dispatch.assign-pickup-driver') }}" method="POST">
            @csrf
            <input type="hidden" name="order_ids" id="selectedOrderIds">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Chọn tài xế <span class="text-danger">*</span></label>
                        <select name="driver_id" class="form-select" required id="driverSelect">
                            <option value="">-- Chọn tài xế --</option>
                            @foreach($drivers ?? [] as $driver)
                            <option value="{{ $driver->id }}">
                                {{ $driver->name }} - {{ $driver->phone }} 
                                @if($driver->area)
                                (Khu vực: {{ $driver->area }})
                                @endif
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Chọn tài xế sẽ đến địa chỉ người gửi để lấy hàng</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Thời gian lấy hàng dự kiến</label>
                        <input type="datetime-local" name="pickup_scheduled_at" class="form-control" value="{{ date('Y-m-d\TH:i') }}">
                        <small class="text-muted">Thời gian tài xế dự kiến đến lấy hàng</small>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Đã chọn <span id="selectedCount">0</span> đơn hàng</strong><br>
                <small>Tài xế sẽ đến địa chỉ người gửi (Nghệ An) để lấy hàng, sau đó đưa về kho Nghệ An</small>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="assignBtn" disabled>
                    <i class="fas fa-check me-2"></i>Phân công tài xế lấy hàng
                </button>
                <button type="button" class="btn btn-success" id="autoAssignBtn" disabled>
                    <i class="fas fa-magic me-2"></i>Điều phối tự động (Random)
                </button>
            </div>
        </form>
        
        <form id="autoAssignForm" action="{{ route('admin.dispatch.auto-assign-pickup-driver') }}" method="POST" style="display: none;">
            @csrf
            <input type="hidden" name="order_ids" id="autoSelectedOrderIds">
            <input type="hidden" name="pickup_scheduled_at" value="{{ date('Y-m-d\TH:i') }}">
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#selectAll').on('change', function() {
        $('.order-checkbox').prop('checked', this.checked);
        updateSelectedCount();
    });
    
    $('.order-checkbox').on('change', function() {
        updateSelectedCount();
    });
    
    function updateSelectedCount() {
        const selected = $('.order-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        $('#selectedCount').text(selected.length);
        $('#selectedOrderIds').val(JSON.stringify(selected));
        $('#autoSelectedOrderIds').val(JSON.stringify(selected));
        $('#assignBtn').prop('disabled', selected.length === 0);
        $('#autoAssignBtn').prop('disabled', selected.length === 0);
    }
    
    function autoAssignDriver() {
        const selected = JSON.parse($('#autoSelectedOrderIds').val() || '[]');
        if (selected.length === 0) {
            alert('Vui lòng chọn ít nhất một đơn hàng');
            return false;
        }
        
        if (confirm(`Bạn có chắc muốn tự động phân công tài xế ngẫu nhiên cho ${selected.length} đơn hàng đã chọn?`)) {
            $('#autoAssignForm').submit();
        }
    }
    
    $('#assignDriverForm').on('submit', function(e) {
        const selected = JSON.parse($('#selectedOrderIds').val() || '[]');
        if (selected.length === 0) {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất một đơn hàng');
            return false;
        }
    });
    
    // Xử lý nút Random
    $('#autoAssignBtn').on('click', function() {
        autoAssignDriver();
    });
});

function autoAssignDriver() {
    const selected = JSON.parse($('#autoSelectedOrderIds').val() || '[]');
    if (selected.length === 0) {
        alert('Vui lòng chọn ít nhất một đơn hàng');
        return false;
    }
    
    if (confirm(`Bạn có chắc muốn tự động phân công tài xế ngẫu nhiên cho ${selected.length} đơn hàng đã chọn?`)) {
        $('#autoAssignForm').submit();
    }
}

function updatePickupStatus(orderId, status) {
    const statusText = status === 'picking_up' ? 'đang đi lấy hàng' : 'đã lấy hàng';
    if (!confirm(`Xác nhận cập nhật trạng thái: Tài xế ${statusText}?`)) {
        return;
    }
    
    $.ajax({
        url: `/admin/dispatch/update-pickup-status/${orderId}`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            status: status,
            notes: status === 'picking_up' ? 'Tài xế đang đi lấy hàng' : 'Tài xế đã lấy hàng và đưa về kho'
        },
        success: function(response) {
            alert('Cập nhật trạng thái thành công!');
            location.reload();
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'Có lỗi xảy ra khi cập nhật trạng thái';
            alert(message);
        }
    });
}
</script>
@endpush

