@extends('admin.layout')

@section('title', 'Giao Hàng')
@section('page-title', 'Giao Hàng')

@section('content')
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Đã xuất kho - Đang vận chuyển</h6>
                <h3 class="mb-0">{{ $stats['in_transit'] ?? 0 }}</h3>
                <small>Đã xuất kho, đang đi các tỉnh</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Đang giao</h6>
                <h3 class="mb-0">{{ $stats['out_for_delivery'] ?? 0 }}</h3>
                <small>Đã phân công tài xế giao</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Đã giao hôm nay</h6>
                <h3 class="mb-0">{{ $stats['delivered_today'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Thất bại hôm nay</h6>
                <h3 class="mb-0">{{ $stats['failed_today'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Đơn hàng đã xuất kho - Đang vận chuyển</h5>
            <small>Danh sách đơn hàng đã xuất kho từ Nghệ An, đang vận chuyển đi các tỉnh</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" action="{{ route('admin.delivery.index') }}" id="filterProvinceInTransitForm" class="mb-0 d-inline-block">
                @if(request('province_delivery'))
                <input type="hidden" name="province_delivery" value="{{ request('province_delivery') }}">
                @endif
                <select name="province_in_transit" id="filterProvinceInTransit" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tất cả tỉnh/TP</option>
                    @php
                        // Load tất cả 63 tỉnh từ file JSON
                        $addressesJson = file_get_contents(public_path('data/vietnam-addresses-full.json'));
                        $addresses = json_decode($addressesJson, true);
                        $allProvinces = collect($addresses['provinces'] ?? [])->pluck('name')->sort()->values();
                    @endphp
                    @foreach($allProvinces as $province)
                    <option value="{{ $province }}" {{ request('province_in_transit') == $province ? 'selected' : '' }}>{{ $province }}</option>
                    @endforeach
                </select>
            </form>
            <button type="button" class="btn btn-sm btn-success" id="bulkAssignDriverBtn" disabled onclick="return bulkAssignDriver();">
                <i class="fas fa-user-plus me-1"></i>Phân công tài xế nhiều đơn (<span id="selectedInTransitCount">0</span>)
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="inTransitTable">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAllInTransit" title="Chọn tất cả">
                        </th>
                        <th>Mã vận đơn</th>
                        <th>Người nhận</th>
                        <th>Địa chỉ giao</th>
                        <th>Tỉnh/TP</th>
                        <th>Tuyến vận chuyển</th>
                        <th>COD</th>
                        <th>Ngày xuất kho</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ordersInTransit ?? [] as $order)
                    <tr>
                        <td>
                            <input type="checkbox" class="order-checkbox-intransit" value="{{ $order->id }}" 
                                   data-tracking="{{ $order->tracking_number }}"
                                   data-receiver-province="{{ $order->receiver_province }}">
                        </td>
                        <td><strong>{{ $order->tracking_number }}</strong></td>
                        <td>
                            {{ $order->receiver_name }}<br>
                            <small class="text-muted">{{ $order->receiver_phone }}</small>
                        </td>
                        <td>{{ $order->receiver_address }}</td>
                        <td><span class="badge bg-info">{{ $order->receiver_province }}</span></td>
                        <td>
                            @if($order->route)
                            <small>{{ $order->route->name }}</small><br>
                            <small class="text-muted">{{ $order->route->from_province }} → {{ $order->route->to_province }}</small>
                            @else
                            <span class="text-muted">Chưa có</span>
                            @endif
                        </td>
                        <td>{{ number_format($order->cod_amount) }} đ</td>
                        <td>
                            @php
                                $lastOutTransaction = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                                    ->where('type', 'out')
                                    ->orderBy('transaction_date', 'desc')
                                    ->first();
                            @endphp
                            @if($lastOutTransaction)
                            <small>{{ $lastOutTransaction->transaction_date->format('d/m/Y H:i') }}</small>
                            @else
                            <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-success" onclick="assignDriver({{ $order->id }})" title="Phân công tài xế giao hàng">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-primary" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">
                            <i class="fas fa-truck me-2"></i>Không có đơn hàng nào đang vận chuyển
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Đơn hàng đang giao</h5>
            <small>Danh sách đơn hàng đã được phân công tài xế giao hàng</small>
        </div>
        <div>
            <form method="GET" action="{{ route('admin.delivery.index') }}" id="filterProvinceDeliveryForm" class="mb-0 d-inline-block">
                @if(request('province_in_transit'))
                <input type="hidden" name="province_in_transit" value="{{ request('province_in_transit') }}">
                @endif
                <select name="province_delivery" id="filterProvinceDelivery" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tất cả tỉnh/TP</option>
                    @php
                        // Load tất cả 63 tỉnh từ file JSON
                        $addressesJson = file_get_contents(public_path('data/vietnam-addresses-full.json'));
                        $addresses = json_decode($addressesJson, true);
                        $allProvinces = collect($addresses['provinces'] ?? [])->pluck('name')->sort()->values();
                    @endphp
                    @foreach($allProvinces as $province)
                    <option value="{{ $province }}" {{ request('province_delivery') == $province ? 'selected' : '' }}>{{ $province }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="deliveryTable">
                <thead>
                    <tr>
                        <th>Mã vận đơn</th>
                        <th>Người nhận</th>
                        <th>Địa chỉ giao</th>
                        <th>Tỉnh/TP</th>
                        <th>COD</th>
                        <th>Tài xế giao</th>
                        <th>Thời gian dự kiến</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ordersReadyForDelivery ?? [] as $order)
                    <tr>
                        <td><strong>{{ $order->tracking_number }}</strong></td>
                        <td>
                            {{ $order->receiver_name }}<br>
                            <small class="text-muted">{{ $order->receiver_phone }}</small>
                        </td>
                        <td>{{ $order->receiver_address }}</td>
                        <td><span class="badge bg-info">{{ $order->receiver_province }}</span></td>
                        <td>{{ number_format($order->cod_amount) }} đ</td>
                        <td>
                            @if($order->deliveryDriver)
                            <strong>{{ $order->deliveryDriver->name }}</strong><br>
                            <small class="text-muted">{{ $order->deliveryDriver->phone }}</small>
                            @else
                            <span class="text-muted">Chưa phân công</span>
                            @endif
                        </td>
                        <td>
                            @if($order->delivery_scheduled_at)
                            <small>{{ $order->delivery_scheduled_at->format('d/m/Y H:i') }}</small>
                            @else
                            <span class="text-muted">Chưa có</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-primary" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            <i class="fas fa-shipping-fast me-2"></i>Không có đơn hàng nào đang giao
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Assign Driver Modal -->
<div class="modal fade" id="assignDriverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="assignDriverForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Phân công tài xế giao hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="orderId">
                    <div class="mb-3">
                        <label class="form-label">Chọn tài xế <span class="text-danger">*</span></label>
                        <select name="driver_id" class="form-select" required>
                            <option value="">-- Chọn tài xế --</option>
                            @foreach($drivers ?? [] as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->name }} - {{ $driver->phone }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Thời gian giao dự kiến</label>
                        <input type="datetime-local" name="delivery_scheduled_at" class="form-control" value="{{ date('Y-m-d\TH:i') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Phân công</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Assign Driver Modal -->
<div class="modal fade" id="bulkAssignDriverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bulkAssignDriverForm" method="POST" action="{{ route('admin.delivery.bulk-assign-driver') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Phân công tài xế giao hàng nhiều đơn</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="bulkOrderIdsContainer">
                        <!-- Order IDs sẽ được thêm vào đây dưới dạng hidden inputs -->
                    </div>
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <div id="bulkAssignInfo"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Chọn tài xế <span class="text-danger">*</span></label>
                        <select name="driver_id" class="form-select" required id="bulkDriverSelect">
                            <option value="">-- Chọn tài xế --</option>
                            @foreach($drivers ?? [] as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->name }} - {{ $driver->phone }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Tài xế sẽ được phân công cho tất cả đơn hàng đã chọn</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Thời gian giao dự kiến</label>
                        <input type="datetime-local" name="delivery_scheduled_at" class="form-control" value="{{ date('Y-m-d\TH:i') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Phân công cho <span id="bulkSelectedCount">0</span> đơn
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Xử lý chọn nhiều đơn hàng đang vận chuyển
    function updateSelectedInTransitCount() {
        const selected = $('.order-checkbox-intransit:checked').map(function() {
            return $(this).val();
        }).get();
        
        const count = selected.length;
        $('#selectedInTransitCount').text(count);
        $('#bulkAssignDriverBtn').prop('disabled', count === 0);
    }
    
    // DataTable cho bảng đơn hàng đang vận chuyển
    @if(isset($ordersInTransit) && count($ordersInTransit) > 0)
    var inTransitTable = $('#inTransitTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[7, 'desc']],
        drawCallback: function() {
            // Re-bind events sau khi DataTable redraw
            $('.order-checkbox-intransit').off('change').on('change', function() {
                updateSelectedInTransitCount();
                // Cập nhật trạng thái checkbox "Chọn tất cả"
                const total = $('.order-checkbox-intransit').length;
                const checked = $('.order-checkbox-intransit:checked').length;
                $('#selectAllInTransit').prop('checked', total === checked && total > 0);
            });
        }
    });
    
    // Xử lý chọn tất cả (sử dụng event delegation)
    $(document).on('change', '#selectAllInTransit', function() {
        const isChecked = this.checked;
        $('.order-checkbox-intransit').prop('checked', isChecked);
        updateSelectedInTransitCount();
    });
    @else
    // Nếu không có DataTable, bind events trực tiếp
    $('#selectAllInTransit').on('change', function() {
        $('.order-checkbox-intransit').prop('checked', this.checked);
        updateSelectedInTransitCount();
    });
    
    $('.order-checkbox-intransit').on('change', function() {
        updateSelectedInTransitCount();
        const total = $('.order-checkbox-intransit').length;
        const checked = $('.order-checkbox-intransit:checked').length;
        $('#selectAllInTransit').prop('checked', total === checked && total > 0);
    });
    @endif
    
    // Trigger update on page load
    updateSelectedInTransitCount();
    
    // DataTable cho bảng đơn hàng đang giao
    @if(isset($ordersReadyForDelivery) && count($ordersReadyForDelivery) > 0)
    var deliveryTable = $('#deliveryTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[6, 'desc']]
    });
    @endif
});

function assignDriver(orderId) {
    $('#orderId').val(orderId);
    $('#assignDriverForm').attr('action', '/admin/delivery/assign-driver/' + orderId);
    $('#assignDriverModal').modal('show');
}

function bulkAssignDriver() {
    console.log('bulkAssignDriver called');
    
    // Lấy tất cả checkbox đã checked (kể cả trong DataTable)
    const selected = [];
    $('.order-checkbox-intransit:checked').each(function() {
        selected.push($(this).val());
    });
    
    console.log('Selected orders:', selected);
    
    if (selected.length === 0) {
        alert('Vui lòng chọn ít nhất một đơn hàng để phân công tài xế.');
        return false;
    }
    
    // Lấy thông tin các tỉnh nhận của đơn hàng đã chọn
    const provinces = [];
    $('.order-checkbox-intransit:checked').each(function() {
        const province = $(this).data('receiver-province');
        if (province && !provinces.includes(province)) {
            provinces.push(province);
        }
    });
    
    // Xóa các hidden input cũ
    $('#bulkOrderIdsContainer').empty();
    
    // Tạo hidden inputs cho từng order_id (để gửi dưới dạng array)
    selected.forEach(function(orderId) {
        $('#bulkOrderIdsContainer').append(
            '<input type="hidden" name="order_ids[]" value="' + orderId + '">'
        );
    });
    
    // Hiển thị modal chọn tài xế
    $('#bulkAssignDriverModal').modal('show');
    $('#bulkSelectedCount').text(selected.length);
    
    // Hiển thị thông tin
    let infoText = `Đã chọn ${selected.length} đơn hàng`;
    if (provinces.length > 0) {
        infoText += `\nCác tỉnh nhận: ${provinces.join(', ')}`;
    }
    $('#bulkAssignInfo').html(infoText.replace(/\n/g, '<br>'));
    
    return false;
}
</script>
@endpush

