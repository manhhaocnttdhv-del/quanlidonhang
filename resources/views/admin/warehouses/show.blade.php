@extends('admin.layout')

@section('title', 'Chi Tiết Kho')
@section('page-title', 'Chi Tiết Kho: ' . $warehouse->name)

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Thông tin kho</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Mã kho:</strong> {{ $warehouse->code }}</p>
                        <p><strong>Tên kho:</strong> {{ $warehouse->name }}</p>
                        <p><strong>Địa chỉ:</strong> {{ $warehouse->address }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Số điện thoại:</strong> {{ $warehouse->phone ?? 'N/A' }}</p>
                        <p><strong>Quản lý:</strong> {{ $warehouse->manager_name ?? 'N/A' }}</p>
                        <p><strong>Trạng thái:</strong> 
                            <span class="badge bg-{{ $warehouse->is_active ? 'success' : 'secondary' }}">
                                {{ $warehouse->is_active ? 'Hoạt động' : 'Tạm dừng' }}
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
                <h6 class="mb-0">Thống kê</h6>
            </div>
            <div class="card-body">
                <p><strong>Tồn kho hiện tại:</strong> {{ $inventory['total_orders'] ?? 0 }} đơn</p>
                <p><strong>Đã nhập hôm nay:</strong> {{ $inventory['today_in'] ?? 0 }} đơn</p>
                <p><strong>Đã xuất hôm nay:</strong> {{ $inventory['today_out'] ?? 0 }} đơn</p>
                <p><strong>Hàng vừa nhận từ tài xế:</strong> 
                    <span class="badge bg-success">{{ count($inventory['today_received'] ?? []) }} đơn</span>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-inbox me-2"></i>Nhập kho</h5>
        <small class="text-muted">Nhập đơn hàng từ kho các tỉnh khác gửi về hoặc đơn hàng khác</small>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.warehouses.receive-order') }}" method="POST">
            @csrf
            <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Mã vận đơn <span class="text-danger">*</span></label>
                        <input type="text" name="order_id" class="form-control" placeholder="Nhập mã vận đơn hoặc ID" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Từ kho (nếu có)</label>
                        <select name="from_warehouse_id" class="form-select">
                            <option value="">-- Chọn kho gửi --</option>
                            @foreach(\App\Models\Warehouse::where('id', '!=', $warehouse->id)->where('is_active', true)->get() as $w)
                            <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->province }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Số phiếu nhập</label>
                        <input type="text" name="reference_number" class="form-control">
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Ghi chú</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Ví dụ: Nhận từ kho Hà Nội, kho Hồ Chí Minh..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-arrow-down me-2"></i>Nhập kho
            </button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Hàng vừa nhận từ kho khác hôm nay</h5>
        <small>Danh sách đơn hàng từ kho các tỉnh khác gửi về kho {{ $warehouse->name }} hôm nay</small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>Mã vận đơn</th>
                        <th>Từ kho</th>
                        <th>Người nhận</th>
                        <th>Tỉnh/TP</th>
                        <th>Thời gian nhận</th>
                        <th>Ghi chú</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventory['today_received_from_warehouses'] ?? [] as $transaction)
                    <tr>
                        <td><strong>{{ $transaction->order->tracking_number ?? 'N/A' }}</strong></td>
                        <td>
                            @if($transaction->notes)
                            <small>{{ $transaction->notes }}</small>
                            @else
                            <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>{{ $transaction->order->receiver_name ?? 'N/A' }}</td>
                        <td><span class="badge bg-info">{{ $transaction->order->receiver_province ?? 'N/A' }}</span></td>
                        <td><small>{{ $transaction->transaction_date->format('d/m/Y H:i') }}</small></td>
                        <td><small>{{ $transaction->notes ?? '-' }}</small></td>
                        <td>
                            @if($transaction->order)
                            <a href="{{ route('admin.orders.show', $transaction->order->id) }}" class="btn btn-sm btn-primary" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            <i class="fas fa-inbox me-2"></i>Chưa có hàng nào từ kho khác gửi về hôm nay
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-truck-loading me-2"></i>Đơn hàng tài xế lấy từ khách hàng</h5>
        <small>Tất cả đơn hàng từ "Điều phối nhận" - Tài xế đã được phân công lấy hàng từ người gửi (khách hàng)</small>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Hôm nay:</strong> {{ count($inventory['today_received_from_pickup'] ?? []) }} đơn | 
            <strong>Tổng trong kho:</strong> {{ count($inventory['all_orders_from_pickup'] ?? []) }} đơn
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="pickupOrdersTable">
                <thead>
                    <tr>
                        <th>Mã vận đơn</th>
                        <th>Trạng thái</th>
                        <th>Tài xế lấy hàng</th>
                        <th>Người gửi</th>
                        <th>Người nhận</th>
                        <th>Tỉnh/TP nhận</th>
                        <th>Trọng lượng</th>
                        <th>Thời gian lấy hàng</th>
                        <th>Thời gian về kho</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventory['all_orders_from_pickup'] ?? [] as $order)
                    <tr>
                        <td><strong>{{ $order->tracking_number }}</strong></td>
                        <td>
                            @if($order->status === 'in_warehouse')
                            <span class="badge bg-success" title="Đã về kho">
                                <i class="fas fa-warehouse me-1"></i>Đã về kho
                            </span>
                            @elseif($order->status === 'picked_up')
                            <span class="badge bg-info" title="Đã lấy hàng">
                                <i class="fas fa-check me-1"></i>Đã lấy
                            </span>
                            @elseif($order->status === 'picking_up')
                            <span class="badge bg-warning" title="Đang lấy hàng">
                                <i class="fas fa-walking me-1"></i>Đang lấy
                            </span>
                            @elseif($order->status === 'pickup_pending')
                            <span class="badge bg-secondary" title="Đã phân công">
                                <i class="fas fa-clock me-1"></i>Đã phân công
                            </span>
                            @else
                            <span class="badge bg-secondary">{{ $order->status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($order->pickupDriver)
                            <strong>{{ $order->pickupDriver->name }}</strong><br>
                            <small class="text-muted"><i class="fas fa-phone me-1"></i>{{ $order->pickupDriver->phone }}</small>
                            @else
                            <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $order->sender_name }}</strong><br>
                            <small class="text-muted">{{ $order->sender_phone }}</small>
                        </td>
                        <td>{{ $order->receiver_name }}</td>
                        <td><span class="badge bg-info">{{ $order->receiver_province }}</span></td>
                        <td>{{ $order->weight }} kg</td>
                        <td>
                            @if($order->picked_up_at)
                            <small>{{ $order->picked_up_at->format('d/m/Y H:i') }}</small>
                            @elseif($order->pickup_scheduled_at)
                            <small class="text-muted">DK: {{ $order->pickup_scheduled_at->format('d/m/Y H:i') }}</small>
                            @else
                            <span class="text-muted">Chưa lấy</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $lastTransaction = \App\Models\WarehouseTransaction::where('warehouse_id', $warehouse->id)
                                    ->where('order_id', $order->id)
                                    ->where('type', 'in')
                                    ->orderBy('transaction_date', 'desc')
                                    ->first();
                            @endphp
                            @if($lastTransaction)
                            <small class="text-success">{{ $lastTransaction->transaction_date->format('d/m/Y H:i') }}</small>
                            @elseif($order->status === 'in_warehouse' && $order->picked_up_at)
                            <small class="text-success">{{ $order->picked_up_at->format('d/m/Y H:i') }}</small>
                            @else
                            <span class="text-muted">Chưa về kho</span>
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
                        <td colspan="10" class="text-center text-muted">
                            <i class="fas fa-truck me-2"></i>Chưa có đơn hàng nào từ "Điều phối nhận"
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Hàng vừa nhận từ tài xế hôm nay</h5>
        <small>Danh sách đơn hàng tài xế đã lấy từ người gửi và đưa về kho hôm nay</small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>Mã vận đơn</th>
                        <th>Tài xế lấy hàng</th>
                        <th>Người nhận</th>
                        <th>Tỉnh/TP</th>
                        <th>Thời gian lấy hàng</th>
                        <th>Thời gian về kho</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventory['today_received'] ?? [] as $order)
                    <tr>
                        <td><strong>{{ $order->tracking_number }}</strong></td>
                        <td>
                            @if($order->pickupDriver)
                            <i class="fas fa-user-tie me-1"></i>{{ $order->pickupDriver->name }}<br>
                            <small class="text-muted">{{ $order->pickupDriver->phone }}</small>
                            @else
                            <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>{{ $order->receiver_name }}</td>
                        <td><span class="badge bg-info">{{ $order->receiver_province }}</span></td>
                        <td>
                            @if($order->picked_up_at)
                            <small>{{ $order->picked_up_at->format('d/m/Y H:i') }}</small>
                            @else
                            <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($order->picked_up_at)
                            <small class="text-success">{{ $order->picked_up_at->format('H:i') }}</small>
                            @else
                            <span class="text-muted">N/A</span>
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
                        <td colspan="7" class="text-center text-muted">
                            <i class="fas fa-inbox me-2"></i>Chưa có hàng nào được tài xế đưa về kho hôm nay
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4" style="display: none;">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-box-open me-2"></i>Xuất kho - Điều phối đi các tỉnh</h5>
        <small class="text-muted">Xuất kho đơn lẻ hoặc nhiều đơn cùng lúc</small>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#singleRelease" role="tab">Xuất kho đơn lẻ</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#bulkRelease" role="tab">Xuất kho nhiều đơn</a>
            </li>
        </ul>
        
        <div class="tab-content">
            <!-- Tab xuất kho đơn lẻ -->
            <div class="tab-pane fade show active" id="singleRelease" role="tabpanel">
                <form id="releaseOrderForm" action="{{ route('admin.warehouses.release-order') }}" method="POST">
                    @csrf
                    <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Mã vận đơn <span class="text-danger">*</span></label>
                                <input type="text" name="order_id" id="releaseOrderId" class="form-control" placeholder="Nhập mã vận đơn hoặc ID" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Tuyến vận chuyển</label>
                                <select name="route_id" class="form-select">
                                    <option value="">-- Chọn tuyến --</option>
                                    @foreach($routes ?? [] as $route)
                                    <option value="{{ $route->id }}">
                                        {{ $route->name }} ({{ $route->from_province }} → {{ $route->to_province }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Số phiếu xuất</label>
                                <input type="text" name="reference_number" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi chú</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Ghi chú khi xuất kho"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-arrow-up me-2"></i>Xuất kho - Điều phối đi
                    </button>
                </form>
            </div>
            
            <!-- Tab xuất kho nhiều đơn -->
            <div class="tab-pane fade" id="bulkRelease" role="tabpanel">
                <form id="bulkReleaseForm" action="{{ route('admin.warehouses.bulk-release-order') }}" method="POST">
                    @csrf
                    <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
                    <input type="hidden" name="order_ids" id="bulkOrderIds">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Vui lòng chọn đơn hàng từ bảng "Tồn kho hiện tại" bên dưới, sau đó chọn tuyến vận chuyển và click "Xuất kho nhiều đơn".
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tuyến vận chuyển</label>
                                <select name="route_id" id="bulkRouteId" class="form-select">
                                    <option value="">-- Tự động chọn theo tỉnh nhận --</option>
                                    @foreach($routes ?? [] as $route)
                                    <option value="{{ $route->id }}" 
                                            data-from="{{ $route->from_province }}" 
                                            data-to="{{ $route->to_province }}">
                                        {{ $route->name }} ({{ $route->from_province }} → {{ $route->to_province }})
                                    </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Tuyến sẽ tự động chọn từ Nghệ An đến tỉnh nhận khi chọn đơn hàng</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Số phiếu xuất</label>
                                <input type="text" name="reference_number" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi chú</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Ghi chú khi xuất kho nhiều đơn"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" id="bulkReleaseSubmitBtn" disabled>
                        <i class="fas fa-arrow-up me-2"></i>Xuất kho nhiều đơn (<span id="bulkSelectedCount">0</span> đơn)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal xuất kho nhiều đơn -->
<div class="modal fade" id="bulkReleaseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="bulkReleaseForm" action="{{ route('admin.warehouses.bulk-release-order') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Xuất kho nhiều đơn</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
                    <input type="hidden" name="order_ids" id="bulkOrderIdsModal">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Đã chọn <strong id="bulkModalSelectedCount">0</strong> đơn hàng. Chọn tuyến vận chuyển và click "Xuất kho" để hoàn tất.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tuyến vận chuyển</label>
                                <select name="route_id" id="bulkRouteIdModal" class="form-select">
                                    <option value="">-- Tự động chọn theo tỉnh nhận --</option>
                                    @foreach($routes ?? [] as $route)
                                    <option value="{{ $route->id }}" 
                                            data-from="{{ $route->from_province }}" 
                                            data-to="{{ $route->to_province }}">
                                        {{ $route->name }} ({{ $route->from_province }} → {{ $route->to_province }})
                                    </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Tuyến sẽ tự động chọn từ Nghệ An đến tỉnh nhận khi chọn đơn hàng</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Số phiếu xuất</label>
                                <input type="text" name="reference_number" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi chú</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Ghi chú khi xuất kho nhiều đơn"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-arrow-up me-2"></i>Xuất kho
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>Tồn kho hiện tại</h5>
            <small class="text-muted">Tất cả đơn hàng trong kho: từ tài xế lấy về + từ kho khác gửi về</small>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" action="{{ route('admin.warehouses.show', $warehouse->id) }}" id="filterProvinceForm" class="mb-0">
                <select name="province" id="filterProvince" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tất cả tỉnh/TP</option>
                    @php
                        // Load tất cả 63 tỉnh từ file JSON
                        $addressesJson = file_get_contents(public_path('data/vietnam-addresses-full.json'));
                        $addresses = json_decode($addressesJson, true);
                        $allProvinces = collect($addresses['provinces'] ?? [])->pluck('name')->sort()->values();
                    @endphp
                    @foreach($allProvinces as $province)
                    <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>{{ $province }}</option>
                    @endforeach
                </select>
            </form>
            <button type="button" class="btn btn-sm btn-success" id="bulkReleaseBtn" disabled onclick="bulkReleaseOrders()">
                <i class="fas fa-arrow-up me-1"></i>Xuất kho nhiều đơn (<span id="selectedCount">0</span>)
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-3" id="selectionInfo" style="display: none;">
            <i class="fas fa-info-circle me-2"></i>
            Đã chọn <strong id="selectedCountText">0</strong> đơn hàng. Chọn tuyến vận chuyển và click "Xuất kho nhiều đơn" để xuất kho hàng loạt.
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="inventoryTable">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAllOrders" title="Chọn tất cả">
                        </th>
                        <th>Mã vận đơn</th>
                        <th>Nguồn</th>
                        <th>Người nhận</th>
                        <th>Địa chỉ nhận</th>
                        <th>Tỉnh/TP</th>
                        <th>Trọng lượng</th>
                        <th>Tuyến</th>
                        <th>Ngày nhập kho</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventory['orders'] ?? [] as $order)
                    <tr>
                        <td>
                            <input type="checkbox" class="order-checkbox" value="{{ $order->id }}" 
                                   data-tracking="{{ $order->tracking_number }}"
                                   data-receiver-province="{{ $order->receiver_province }}"
                                   data-route-id="{{ $routesFromNgheAn[$order->receiver_province]->id ?? '' }}">
                        </td>
                        <td><strong>{{ $order->tracking_number }}</strong></td>
                        <td>
                            @if($order->pickup_driver_id && $order->picked_up_at)
                            <span class="badge bg-success" title="Tài xế lấy từ người gửi">
                                <i class="fas fa-truck me-1"></i>Tài xế
                            </span>
                            @else
                            <span class="badge bg-info" title="Từ kho khác gửi về">
                                <i class="fas fa-warehouse me-1"></i>Kho khác
                            </span>
                            @endif
                        </td>
                        <td>{{ $order->receiver_name }}</td>
                        <td>{{ $order->receiver_address }}</td>
                        <td><span class="badge bg-info">{{ $order->receiver_province }}</span></td>
                        <td>{{ $order->weight }} kg</td>
                        <td>
                            @if($order->route)
                            <small>{{ $order->route->name }}</small>
                            @else
                            <span class="text-muted">Chưa có</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $lastTransaction = \App\Models\WarehouseTransaction::where('warehouse_id', $warehouse->id)
                                    ->where('order_id', $order->id)
                                    ->where('type', 'in')
                                    ->orderBy('transaction_date', 'desc')
                                    ->first();
                            @endphp
                            @if($lastTransaction)
                            <small>{{ $lastTransaction->transaction_date->format('d/m/Y H:i') }}</small>
                            @elseif($order->picked_up_at)
                            <small>{{ $order->picked_up_at->format('d/m/Y H:i') }}</small>
                            @else
                            <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-success" onclick="quickRelease({{ $order->id }}, '{{ $order->tracking_number }}')" title="Xuất kho nhanh">
                                    <i class="fas fa-arrow-up"></i>
                                </button>
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-primary" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center">Kho trống - Không có đơn hàng nào trong kho</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Only initialize DataTables if there are rows (not empty)
    @if(isset($inventory['orders']) && count($inventory['orders']) > 0)
    var inventoryTable = $('#inventoryTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[7, 'desc']]
    });
    @endif
    
    // DataTable cho bảng đơn hàng từ tài xế
    @if(isset($inventory['all_orders_from_pickup']) && count($inventory['all_orders_from_pickup']) > 0)
    $('#pickupOrdersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[6, 'desc']]
    });
    @endif
});

function quickRelease(orderId, trackingNumber) {
    // Tìm đơn hàng và tự động chọn tuyến
    const orderRow = $(`.order-checkbox[value="${orderId}"]`).closest('tr');
    const receiverProvince = orderRow.find('.order-checkbox').data('receiver-province');
    const routeId = orderRow.find('.order-checkbox').data('route-id');
    
    // Tự động chọn tuyến nếu có
    if (routeId) {
        $('#releaseOrderForm select[name="route_id"]').val(routeId);
    }
    
    if (!confirm(`Xác nhận xuất kho đơn hàng ${trackingNumber}?\nTuyến: ${routeId ? $('#releaseOrderForm select[name="route_id"] option:selected').text() : 'Chưa chọn'}\nĐơn hàng sẽ được chuyển sang trạng thái "Đang vận chuyển" và điều phối đi các tỉnh.`)) {
        return;
    }
    
    $('#releaseOrderId').val(orderId);
    $('#releaseOrderForm').submit();
}

// Xử lý chọn nhiều đơn hàng
$(document).ready(function() {
    $('#selectAllOrders').on('change', function() {
        $('.order-checkbox').prop('checked', this.checked);
        updateSelectedCount();
    });
    
    $('.order-checkbox').on('change', function() {
        updateSelectedCount();
        // Cập nhật trạng thái checkbox "Chọn tất cả"
        const total = $('.order-checkbox').length;
        const checked = $('.order-checkbox:checked').length;
        $('#selectAllOrders').prop('checked', total === checked && total > 0);
    });
    
    function updateSelectedCount() {
        const selected = $('.order-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        const count = selected.length;
        $('#selectedCount').text(count);
        $('#selectedCountText').text(count);
        $('#bulkSelectedCount').text(count);
        $('#bulkOrderIds').val(JSON.stringify(selected));
        
        // Tự động chọn tuyến vận chuyển từ Nghệ An đến tỉnh nhận
        if (count > 0) {
            // Lấy tỉnh nhận của các đơn hàng đã chọn
            const receiverProvinces = $('.order-checkbox:checked').map(function() {
                return $(this).data('receiver-province');
            }).get();
            
            // Lấy route_id từ đơn hàng đầu tiên (hoặc tìm tuyến chung)
            const firstRouteId = $('.order-checkbox:checked').first().data('route-id');
            
            // Nếu tất cả đơn hàng cùng một tỉnh nhận, tự động chọn tuyến
            const uniqueProvinces = [...new Set(receiverProvinces)];
            if (uniqueProvinces.length === 1 && firstRouteId) {
                $('#bulkRouteId').val(firstRouteId);
            } else if (uniqueProvinces.length > 1) {
                // Nhiều tỉnh khác nhau, để trống hoặc chọn tuyến đầu tiên
                $('#bulkRouteId').val(firstRouteId || '');
            }
        }
        
        // Hiển thị/ẩn thông báo và enable/disable nút
        if (count > 0) {
            $('#selectionInfo').show();
            $('#bulkReleaseBtn').prop('disabled', false);
            $('#bulkReleaseSubmitBtn').prop('disabled', false);
        } else {
            $('#selectionInfo').hide();
            $('#bulkReleaseBtn').prop('disabled', true);
            $('#bulkReleaseSubmitBtn').prop('disabled', true);
            $('#bulkRouteId').val(''); // Reset tuyến khi bỏ chọn hết
        }
    }
    
    // Xử lý form xuất kho nhiều đơn trong modal
    $('#bulkReleaseForm').on('submit', function(e) {
        const selected = JSON.parse($('#bulkOrderIdsModal').val() || '[]');
        if (selected.length === 0) {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất một đơn hàng từ bảng "Tồn kho hiện tại"');
            return false;
        }
    });
});

function bulkReleaseOrders() {
    const selected = JSON.parse($('#bulkOrderIds').val() || '[]');
    if (selected.length === 0) {
        alert('Vui lòng chọn ít nhất một đơn hàng để xuất kho.');
        return false;
    }
    
    // Lấy thông tin các tỉnh nhận của đơn hàng đã chọn
    const provinces = [];
    $('.order-checkbox:checked').each(function() {
        const province = $(this).data('receiver-province');
        if (province && !provinces.includes(province)) {
            provinces.push(province);
        }
    });
    
    // Hiển thị thông báo xác nhận
    let confirmMessage = `Xác nhận xuất kho ${selected.length} đơn hàng?\n\n`;
    confirmMessage += `Tuyến vận chuyển: Tự động từ Nghệ An đến các tỉnh nhận\n`;
    if (provinces.length > 0) {
        confirmMessage += `Các tỉnh nhận: ${provinces.join(', ')}\n\n`;
    }
    confirmMessage += `Đơn hàng sẽ được chuyển sang trạng thái "Đang vận chuyển".`;
    
    if (!confirm(confirmMessage)) {
        return false;
    }
    
    // Gửi request xuất kho - Controller sẽ tự động tìm tuyến từ Nghệ An đến tỉnh nhận
    $.ajax({
        url: '{{ route("admin.warehouses.bulk-release-order") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            warehouse_id: {{ $warehouse->id }},
            order_ids: selected,
            notes: 'Xuất kho hàng loạt từ Nghệ An'
        },
        success: function(response) {
            alert('Đã xuất kho thành công ' + (response.data?.success || selected.length) + ' đơn hàng!');
            location.reload();
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'Có lỗi xảy ra khi xuất kho';
            alert(message);
        }
    });
}
</script>
@endpush

