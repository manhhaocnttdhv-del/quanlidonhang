@extends('admin.layout')

@section('title', 'Chi Tiết Kho')
@section('page-title', 'Chi Tiết Kho: ' . $warehouse->name)

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Thông tin kho</h5>
                @if(auth()->user()->canManageWarehouses())
                <a href="{{ route('admin.warehouses.edit', $warehouse->id) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-edit me-1"></i>Sửa kho
                </a>
                @endif
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

<!-- Đơn hàng đang đến kho (chưa nhận) -->
@if(isset($inventory['orders_incoming']) && count($inventory['orders_incoming']) > 0)
<div class="card mb-4">
    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-truck-loading me-2"></i>Đơn hàng đang đến kho (Chưa nhận)</h5>
            <small>Danh sách đơn hàng từ kho khác đang vận chuyển đến kho {{ $warehouse->name }} - Tổng: {{ count($inventory['orders_incoming']) }} đơn</small>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-warning mb-3">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Lưu ý:</strong> Đây là các đơn hàng đang được vận chuyển đến kho này. Vui lòng nhận đơn hàng khi hàng đã đến kho.
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Mã vận đơn</th>
                        <th>Từ kho</th>
                        <th>Người nhận</th>
                        <th>Địa chỉ nhận</th>
                        <th>Tỉnh/TP nhận</th>
                        <th>Tuyến vận chuyển</th>
                        <th>Ngày xuất kho</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inventory['orders_incoming'] ?? [] as $order)
                    <tr>
                        <td><strong>{{ $order->tracking_number }}</strong></td>
                        <td>
                            @if($order->warehouse)
                            <span class="badge bg-secondary">{{ $order->warehouse->name }}</span><br>
                            <small class="text-muted">{{ $order->warehouse->province ?? '' }}</small>
                            @else
                            <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            {{ $order->receiver_name }}<br>
                            <small class="text-muted">{{ $order->receiver_phone }}</small>
                        </td>
                        <td>{{ $order->receiver_address }}</td>
                        <td><span class="badge bg-info">{{ $order->receiver_province }}</span></td>
                        <td>
                            @if($order->route)
                            <small>{{ $order->route->name }}</small>
                            @else
                            <span class="text-muted">Chưa có</span>
                            @endif
                        </td>
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
                            <form action="{{ route('admin.warehouses.receive-order') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
                                <input type="hidden" name="order_id" value="{{ $order->id }}">
                                <input type="hidden" name="from_warehouse_id" value="{{ $order->warehouse_id }}">
                                <button type="submit" class="btn btn-sm btn-success" title="Nhận đơn hàng vào kho">
                                    <i class="fas fa-check me-1"></i>Nhận vào kho
                                </button>
                            </form>
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-primary" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Phần "Hàng vừa nhận từ kho khác hôm nay" đã được ẩn để tránh trùng lặp với "Đơn hàng từ kho khác tới" --}}

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

<!-- Đơn hàng từ tài xế về -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center bg-success text-white">
        <div>
            <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Đơn hàng từ tài xế về</h5>
            <small>Tổng: {{ count($inventory['orders_from_pickup'] ?? []) }} đơn</small>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" action="{{ route('admin.warehouses.show', $warehouse->id) }}" id="filterProvinceForm1" class="mb-0">
                <select name="province" id="filterProvince1" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tất cả tỉnh/TP</option>
                    @php
                        $addressesJson = file_get_contents(public_path('data/vietnam-addresses-full.json'));
                        $addresses = json_decode($addressesJson, true);
                        $allProvinces = collect($addresses['provinces'] ?? [])->pluck('name')->sort()->values();
                    @endphp
                    @foreach($allProvinces as $province)
                    <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>{{ $province }}</option>
                    @endforeach
                </select>
            </form>
            <button type="button" class="btn btn-light btn-sm" id="bulkReleaseBtnPickup" disabled onclick="bulkReleaseOrders()">
                <i class="fas fa-arrow-up me-1"></i>Xuất kho nhiều đơn (<span id="selectedCountPickup">0</span>)
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-3" id="selectionInfoPickup" style="display: none;">
            <i class="fas fa-info-circle me-2"></i>
            Đã chọn <strong id="selectedCountTextPickup">0</strong> đơn hàng. Chọn tuyến vận chuyển và click "Xuất kho nhiều đơn" để xuất kho hàng loạt.
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="inventoryTablePickup">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAllOrdersPickup" title="Chọn tất cả">
                        </th>
                        <th>Mã vận đơn</th>
                        <th>Tài xế lấy hàng</th>
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
                    @forelse($inventory['orders_from_pickup'] ?? [] as $order)
                    <tr>
                        <td>
                            <input type="checkbox" class="order-checkbox-pickup" value="{{ $order->id }}" 
                                   data-tracking="{{ $order->tracking_number }}"
                                   data-receiver-province="{{ $order->receiver_province }}"
                                   data-route-id="{{ $routesFromNgheAn[$order->receiver_province]->id ?? '' }}">
                        </td>
                        <td><strong>{{ $order->tracking_number }}</strong></td>
                        <td>
                            @if($order->pickupDriver)
                            <i class="fas fa-user-tie me-1"></i>{{ $order->pickupDriver->name }}<br>
                            <small class="text-muted">{{ $order->pickupDriver->phone ?? '' }}</small>
                            @else
                            <span class="text-muted">N/A</span>
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
                        <td colspan="10" class="text-center text-muted">
                            <i class="fas fa-truck me-2"></i>Chưa có đơn hàng nào từ tài xế về kho
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Đơn hàng từ kho khác tới -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center bg-info text-white">
        <div>
            <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>Đơn hàng từ kho khác tới</h5>
            <small>Tổng: {{ count($inventory['orders_from_other_warehouses'] ?? []) }} đơn</small>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" action="{{ route('admin.warehouses.show', $warehouse->id) }}" id="filterProvinceForm2" class="mb-0">
                <select name="province" id="filterProvince2" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tất cả tỉnh/TP</option>
                    @php
                        $addressesJson = file_get_contents(public_path('data/vietnam-addresses-full.json'));
                        $addresses = json_decode($addressesJson, true);
                        $allProvinces = collect($addresses['provinces'] ?? [])->pluck('name')->sort()->values();
                    @endphp
                    @foreach($allProvinces as $province)
                    <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>{{ $province }}</option>
                    @endforeach
                </select>
            </form>
            <button type="button" class="btn btn-success btn-sm me-2" id="bulkAssignDriverBtnWarehouse" disabled onclick="openAssignDriverModal('from_warehouse')">
                <i class="fas fa-user-plus me-1"></i>Phân công tài xế (<span id="selectedCountWarehouse">0</span>)
            </button>
            {{-- <button type="button" class="btn btn-light btn-sm" id="bulkReleaseBtnWarehouse" disabled onclick="bulkReleaseOrders()">
                <i class="fas fa-arrow-up me-1"></i>Xuất kho nhiều đơn (<span id="selectedCountWarehouse">0</span>)
            </button> --}}
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-3" id="selectionInfoWarehouse" style="display: none;">
            <i class="fas fa-info-circle me-2"></i>
            Đã chọn <strong id="selectedCountTextWarehouse">0</strong> đơn hàng. Chọn tuyến vận chuyển và click "Xuất kho nhiều đơn" để xuất kho hàng loạt.
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="inventoryTableWarehouse">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAllOrdersWarehouse" title="Chọn tất cả">
                        </th>
                        <th>Mã vận đơn</th>
                        <th>Kho gửi</th>
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
                    @forelse($inventory['orders_from_other_warehouses'] ?? [] as $order)
                    <tr>
                        <td>
                            <input type="checkbox" class="order-checkbox-warehouse" value="{{ $order->id }}" 
                                   data-tracking="{{ $order->tracking_number }}"
                                   data-receiver-province="{{ $order->receiver_province }}"
                                   data-route-id="{{ $routesFromNgheAn[$order->receiver_province]->id ?? '' }}"
                                   data-cod-amount="{{ $order->cod_amount ?? 0 }}"
                                   data-shipping-fee="{{ $order->shipping_fee ?? 0 }}">
                        </td>
                        <td><strong>{{ $order->tracking_number }}</strong></td>
                        <td>
                            @php
                                $lastInTransaction = \App\Models\WarehouseTransaction::where('warehouse_id', $warehouse->id)
                                    ->where('order_id', $order->id)
                                    ->where('type', 'in')
                                    ->with('warehouse')
                                    ->orderBy('transaction_date', 'desc')
                                    ->first();
                                $fromWarehouseName = $lastInTransaction && $lastInTransaction->notes ? 
                                    (preg_match('/từ\s+(.+?)(\s*\(|$)/i', $lastInTransaction->notes, $matches) ? $matches[1] : 'Kho khác') : 
                                    'Kho khác';
                            @endphp
                            <span class="badge bg-info" title="Từ kho khác gửi về">
                                <i class="fas fa-warehouse me-1"></i>{{ $fromWarehouseName }}
                            </span>
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
                                @if($order->status === 'out_for_delivery' && $order->delivery_driver_id)
                                    {{-- Đơn hàng đã phân công shipper - Hiển thị trạng thái và nút cập nhật --}}
                                    @if($order->deliveryDriver)
                                        <span class="badge bg-warning me-1" title="Đang giao hàng - Tài xế: {{ $order->deliveryDriver->name }}">
                                            <i class="fas fa-truck me-1"></i>{{ $order->deliveryDriver->name }}
                                        </span>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-success" onclick="updateDeliveryStatus({{ $order->id }}, 'delivered')" title="Cập nhật giao hàng thành công">
                                        <i class="fas fa-check-circle"></i> Thành công
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="updateDeliveryStatus({{ $order->id }}, 'failed')" title="Cập nhật giao hàng thất bại">
                                        <i class="fas fa-times-circle"></i> Thất bại
                                    </button>
                                @elseif($order->status === 'delivered')
                                    {{-- Đơn hàng đã giao thành công - Hiển thị doanh thu --}}
                                    <span class="badge bg-success me-1">
                                        <i class="fas fa-check-circle me-1"></i>Đã giao
                                    </span>
                                    @php
                                        $revenue = ($order->cod_collected ?? $order->cod_amount ?? 0) + ($order->shipping_fee ?? 0);
                                    @endphp
                                    <small class="text-success fw-bold">Doanh thu: {{ number_format($revenue, 0, ',', '.') }} đ</small>
                                @elseif($order->status === 'failed')
                                    {{-- Đơn hàng giao thất bại --}}
                                    <span class="badge bg-danger me-1">
                                        <i class="fas fa-times-circle me-1"></i>Thất bại
                                    </span>
                                @else
                                    {{-- Đơn hàng chưa phân công shipper --}}
                                    @if(!$order->delivery_driver_id)
                                        <button type="button" class="btn btn-sm btn-success" onclick="assignDriverToOrder({{ $order->id }})" title="Phân công tài xế">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
                                    @else
                                        @if($order->deliveryDriver)
                                            <span class="badge bg-success me-1" title="Tài xế: {{ $order->deliveryDriver->name }}">
                                                <i class="fas fa-user me-1"></i>{{ $order->deliveryDriver->name }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">Đã phân công</span>
                                        @endif
                                    @endif
                                    <button type="button" class="btn btn-sm btn-warning" onclick="quickRelease({{ $order->id }}, '{{ $order->tracking_number }}')" title="Xuất kho nhanh">
                                        <i class="fas fa-arrow-up"></i>
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
                        <td colspan="10" class="text-center text-muted">
                            <i class="fas fa-warehouse me-2"></i>Chưa có đơn hàng nào từ kho khác gửi về
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal phân công tài xế -->
<div class="modal fade" id="assignDriverModal" tabindex="-1" aria-labelledby="assignDriverModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignDriverModalLabel">Phân công tài xế</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Lưu ý:</strong> Phân công tài xế shipper để giao hàng đến người nhận (khách hàng). Đơn hàng sẽ chuyển sang trạng thái "Đang giao hàng".
                </div>
                <div class="mb-3">
                    <label class="form-label">Chế độ phân công:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="assignMode" id="assignModeRandom" value="random" checked>
                        <label class="form-check-label" for="assignModeRandom">
                            Phân công Random (Tự động) - Hệ thống tự chọn shipper
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="assignMode" id="assignModeManual" value="manual">
                        <label class="form-check-label" for="assignModeManual">
                            Phân công tài xế riêng (Chọn shipper cụ thể)
                        </label>
                    </div>
                </div>
                <div class="mb-3" id="driverSelectContainer" style="display: none;">
                    <label for="driverSelect" class="form-label">Chọn tài xế shipper:</label>
                    <select class="form-select" id="driverSelect">
                        <option value="">-- Chọn tài xế shipper --</option>
                        @php
                            $warehouseDrivers = \App\Models\Driver::where('warehouse_id', $warehouse->id)
                                ->where('is_active', true)
                                ->where('driver_type', 'shipper')
                                ->get();
                        @endphp
                        @foreach($warehouseDrivers as $driver)
                        <option value="{{ $driver->id }}">{{ $driver->name }} - {{ $driver->phone }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <span id="assignModeHint">Hệ thống sẽ tự động phân công tài xế shipper phù hợp để giao hàng đến người nhận cho các đơn hàng đã chọn.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="confirmAssignDriver()">Xác nhận</button>
            </div>
        </div>
    </div>
</div>

<!-- Đơn hàng đang giao hàng trong khu vực -->
@if(isset($inventory['orders_being_delivered']) && count($inventory['orders_being_delivered']) > 0)
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
        <div>
            <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Đơn hàng đang giao hàng trong khu vực</h5>
            <small>Tổng: {{ count($inventory['orders_being_delivered'] ?? []) }} đơn - Tỉnh nhận: {{ $warehouse->province }}</small>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle me-2"></i>
            Đây là các đơn hàng đang được tài xế giao hàng đến người nhận trong khu vực <strong>{{ $warehouse->province }}</strong>. 
            Các đơn hàng này có thể xuất phát từ kho khác nhưng được giao trong khu vực của kho này.
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Mã vận đơn</th>
                        <th>Kho xuất phát</th>
                        <th>Người nhận</th>
                        <th>Địa chỉ nhận</th>
                        <th>Tài xế giao hàng</th>
                        <th>Thời gian dự kiến</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inventory['orders_being_delivered'] ?? [] as $order)
                    <tr>
                        <td><strong>{{ $order->tracking_number }}</strong></td>
                        <td>
                            @if($order->warehouse)
                            <span class="badge bg-secondary">{{ $order->warehouse->name }}</span>
                            @else
                            <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            {{ $order->receiver_name }}<br>
                            <small class="text-muted">{{ $order->receiver_phone }}</small>
                        </td>
                        <td>{{ $order->receiver_address }}</td>
                        <td>
                            @if($order->deliveryDriver)
                            <strong>{{ $order->deliveryDriver->name }}</strong><br>
                            <small class="text-muted">{{ $order->deliveryDriver->phone }}</small>
                            @else
                            <span class="text-muted">Chưa có</span>
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
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // DataTable cho bảng đơn hàng từ tài xế về
    @if(isset($inventory['orders_from_pickup']) && count($inventory['orders_from_pickup']) > 0)
    $('#inventoryTablePickup').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[8, 'desc']]
    });
    @endif
    
    // DataTable cho bảng đơn hàng từ kho khác tới
    @if(isset($inventory['orders_from_other_warehouses']) && count($inventory['orders_from_other_warehouses']) > 0)
    $('#inventoryTableWarehouse').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[8, 'desc']]
    });
    @endif
    
});

function quickRelease(orderId, trackingNumber) {
    // Tìm đơn hàng và tự động chọn tuyến (có thể ở cả 2 bảng)
    const orderRow = $(`.order-checkbox-pickup[value="${orderId}"], .order-checkbox-warehouse[value="${orderId}"]`).closest('tr');
    const checkbox = orderRow.find('.order-checkbox-pickup, .order-checkbox-warehouse');
    const receiverProvince = checkbox.data('receiver-province');
    const routeId = checkbox.data('route-id');
    
    // Tự động chọn tuyến nếu có
    if (routeId) {
        $('#releaseOrderForm select[name="route_id"]').val(routeId);
    }
    
    if (!confirm(`Xác nhận xuất kho đơn hàng ${trackingNumber}?\n\nĐơn hàng sẽ chuyển sang trang "Giao hàng" để phân công tài xế giao đến người nhận.`)) {
        return;
    }
    
    $('#releaseOrderId').val(orderId);
    $('#releaseOrderForm').submit();
}

// Xử lý chọn nhiều đơn hàng - Đơn hàng từ tài xế về
$(document).ready(function() {
    $('#selectAllOrdersPickup').on('change', function() {
        $('.order-checkbox-pickup').prop('checked', this.checked);
        updateSelectedCount();
    });
    
    $('.order-checkbox-pickup').on('change', function() {
        updateSelectedCount();
        const total = $('.order-checkbox-pickup').length;
        const checked = $('.order-checkbox-pickup:checked').length;
        $('#selectAllOrdersPickup').prop('checked', total === checked && total > 0);
    });
    
    // Xử lý chọn nhiều đơn hàng - Đơn hàng từ kho khác tới
    $('#selectAllOrdersWarehouse').on('change', function() {
        $('.order-checkbox-warehouse').prop('checked', this.checked);
        updateSelectedCount();
    });
    
    $('.order-checkbox-warehouse').on('change', function() {
        updateSelectedCount();
        const total = $('.order-checkbox-warehouse').length;
        const checked = $('.order-checkbox-warehouse:checked').length;
        $('#selectAllOrdersWarehouse').prop('checked', total === checked && total > 0);
    });
    
    function updateSelectedCount() {
        // Gộp tất cả đơn hàng đã chọn từ cả 2 bảng
        const selectedPickup = $('.order-checkbox-pickup:checked').map(function() {
            return $(this).val();
        }).get();
        
        const selectedWarehouse = $('.order-checkbox-warehouse:checked').map(function() {
            return $(this).val();
        }).get();
        
        const selected = [...selectedPickup, ...selectedWarehouse];
        const count = selected.length;
        const countPickup = selectedPickup.length;
        const countWarehouse = selectedWarehouse.length;
        
        // Cập nhật số lượng cho từng phần
        $('#selectedCountPickup').text(countPickup);
        $('#selectedCountTextPickup').text(countPickup);
        $('#selectedCountWarehouse').text(countWarehouse);
        $('#selectedCountTextWarehouse').text(countWarehouse);
        
        // Gộp tất cả vào một mảng để gửi xuất kho
        $('#bulkOrderIds').val(JSON.stringify(selected));
        $('#bulkSelectedCount').text(count);
        
        // Tự động chọn tuyến vận chuyển
        if (count > 0) {
            const allChecked = $('.order-checkbox-pickup:checked, .order-checkbox-warehouse:checked');
            const receiverProvinces = allChecked.map(function() {
                return $(this).data('receiver-province');
            }).get();
            
            const firstRouteId = allChecked.first().data('route-id');
            const uniqueProvinces = [...new Set(receiverProvinces)];
            
            if (uniqueProvinces.length === 1 && firstRouteId) {
                $('#bulkRouteId').val(firstRouteId);
            } else if (uniqueProvinces.length > 1) {
                $('#bulkRouteId').val(firstRouteId || '');
            }
        }
        
        // Hiển thị/ẩn thông báo và enable/disable nút cho từng phần
        if (countPickup > 0) {
            $('#selectionInfoPickup').show();
            $('#bulkReleaseBtnPickup').prop('disabled', false);
        } else {
            $('#selectionInfoPickup').hide();
            $('#bulkReleaseBtnPickup').prop('disabled', true);
        }
        
        if (countWarehouse > 0) {
            $('#selectionInfoWarehouse').show();
            $('#bulkReleaseBtnWarehouse').prop('disabled', false);
            $('#bulkAssignDriverBtnWarehouse').prop('disabled', false);
        } else {
            $('#selectionInfoWarehouse').hide();
            $('#bulkReleaseBtnWarehouse').prop('disabled', true);
            $('#bulkAssignDriverBtnWarehouse').prop('disabled', true);
        }
        
        $('#bulkReleaseSubmitBtn').prop('disabled', count === 0);
        if (count === 0) {
            $('#bulkRouteId').val('');
        }
    }
    
    // Xử lý chế độ phân công tài xế
    $('input[name="assignMode"]').on('change', function() {
        if ($(this).val() === 'manual') {
            $('#driverSelectContainer').show();
            $('#driverSelect').prop('required', true);
            $('#assignModeHint').text('Vui lòng chọn tài xế để phân công cho các đơn hàng đã chọn.');
        } else {
            $('#driverSelectContainer').hide();
            $('#driverSelect').prop('required', false);
            $('#assignModeHint').text('Hệ thống sẽ tự động phân công tài xế phù hợp cho các đơn hàng đã chọn.');
        }
    });
    
});

// Mở modal phân công tài xế
function openAssignDriverModal(section) {
    const selected = [];
    if (section === 'from_warehouse') {
        $('.order-checkbox-warehouse:checked').each(function() {
            selected.push($(this).val());
        });
    }
    
    if (selected.length === 0) {
        alert('Vui lòng chọn ít nhất một đơn hàng để phân công tài xế.');
        return;
    }
    
    $('#assignDriverModal').modal('show');
}

// Phân công tài xế cho một đơn hàng
function assignDriverToOrder(orderId) {
    // Mở modal với 1 đơn hàng
    $('.order-checkbox-warehouse').prop('checked', false);
    $('.order-checkbox-warehouse[value="' + orderId + '"]').prop('checked', true);
    updateSelectedCount();
    openAssignDriverModal('from_warehouse');
}

// Xác nhận phân công tài xế
function confirmAssignDriver() {
    const selected = [];
    $('.order-checkbox-warehouse:checked').each(function() {
        selected.push($(this).val());
    });
    
    if (selected.length === 0) {
        alert('Vui lòng chọn ít nhất một đơn hàng.');
        return;
    }
    
    const assignMode = $('input[name="assignMode"]:checked').val();
    const driverId = $('#driverSelect').val();
    
    if (assignMode === 'manual' && !driverId) {
        alert('Vui lòng chọn tài xế.');
        return;
    }
    
    // Gọi API phân công tài xế
    $.ajax({
        url: '{{ route("admin.delivery.bulk-assign-driver") }}',
        method: 'POST',
        data: {
            order_ids: selected,
            assign_mode: assignMode,
            driver_id: assignMode === 'manual' ? driverId : null,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (typeof showNotification === 'function') {
                showNotification('success', response.message || 'Phân công tài xế thành công!');
            } else {
                alert(response.message || 'Phân công tài xế thành công!');
            }
            $('#assignDriverModal').modal('hide');
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        },
        error: function(xhr) {
            let errorMessage = 'Có lỗi xảy ra khi phân công tài xế.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            if (typeof showNotification === 'function') {
                showNotification('error', errorMessage);
            } else {
                alert(errorMessage);
            }
        }
    });
}

function bulkReleaseOrders() {
    // Gộp tất cả đơn hàng đã chọn từ cả 2 bảng
    const selectedPickup = $('.order-checkbox-pickup:checked').map(function() {
        return $(this).val();
    }).get();
    
    const selectedWarehouse = $('.order-checkbox-warehouse:checked').map(function() {
        return $(this).val();
    }).get();
    
    const selected = [...selectedPickup, ...selectedWarehouse];
    
    if (selected.length === 0) {
        alert('Vui lòng chọn ít nhất một đơn hàng để xuất kho.');
        return false;
    }
    
    // Lấy thông tin các tỉnh nhận của đơn hàng đã chọn
    const provinces = [];
    $('.order-checkbox-pickup:checked, .order-checkbox-warehouse:checked').each(function() {
        const province = $(this).data('receiver-province');
        if (province && !provinces.includes(province)) {
            provinces.push(province);
        }
    });
    
    // Hiển thị thông báo xác nhận
    let confirmMessage = `Xác nhận xuất kho ${selected.length} đơn hàng?\n\n`;
    if (provinces.length > 0) {
        confirmMessage += `Các tỉnh nhận: ${provinces.join(', ')}\n\n`;
    }
    confirmMessage += `Đơn hàng sẽ chuyển sang trang "Giao hàng" để phân công tài xế giao đến người nhận.`;
    
    if (!confirm(confirmMessage)) {
        return false;
    }
    
    // Gửi request xuất kho (backend sẽ tự tạo notes mặc định)
    $.ajax({
        url: '{{ route("admin.warehouses.bulk-release-order") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            warehouse_id: {{ $warehouse->id }},
            order_ids: selected
            // Không truyền notes, để backend tự động tạo từ warehouse name
        },
        success: function(response) {
            alert('Đã xuất kho thành công ' + (response.data?.success || selected.length) + ' đơn hàng!\n\nĐang chuyển sang trang Giao hàng...');
            window.location.href = '{{ route("admin.delivery.index") }}';
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'Có lỗi xảy ra khi xuất kho';
            alert(message);
        }
    });
}

// Cập nhật trạng thái giao hàng (thành công hoặc thất bại)
function updateDeliveryStatus(orderId, status) {
    let data = {
        status: status,
        _token: '{{ csrf_token() }}'
    };
    
    if (status === 'delivered') {
        // Lấy thông tin đơn hàng từ data attributes
        const orderCheckbox = $(`input[value="${orderId}"].order-checkbox-warehouse`);
        const codAmount = parseFloat(orderCheckbox.data('cod-amount') || 0);
        const currentShippingFee = parseFloat(orderCheckbox.data('shipping-fee') || 0);
        
        // Nhập COD đã thu
        // Tính tổng cần thu (COD + phí vận chuyển)
        const totalToCollect = codAmount + (currentShippingFee || 0);
        
        let codPromptMessage = 'Nhập số tiền COD đã thu (đ):\n\n';
        codPromptMessage += `COD cần thu: ${codAmount.toLocaleString('vi-VN')} đ\n`;
        if (currentShippingFee > 0) {
            codPromptMessage += `Phí vận chuyển: ${currentShippingFee.toLocaleString('vi-VN')} đ\n`;
        }
        codPromptMessage += `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`;
        codPromptMessage += `TỔNG CẦN THU: ${totalToCollect.toLocaleString('vi-VN')} đ\n\n`;
        codPromptMessage += '(Nhập số tiền COD đã thu thực tế từ khách hàng)';
        
        const codInput = prompt(codPromptMessage, codAmount.toString());
        if (codInput === null) return; // User cancelled
        
        const codCollected = parseFloat(codInput.replace(/[^\d]/g, '')) || 0;
        if (codCollected < 0) {
            alert('Số tiền COD không hợp lệ.');
            return;
        }
        
        // Nhập phí vận chuyển
        let shippingPromptMessage = 'Nhập phí vận chuyển (đ):\n\n';
        if (currentShippingFee > 0) {
            shippingPromptMessage += `Phí vận chuyển ước tính: ${currentShippingFee.toLocaleString('vi-VN')} đ\n`;
        }
        shippingPromptMessage += '\n(Nhập phí vận chuyển thực tế)';
        
        const shippingInput = prompt(shippingPromptMessage, currentShippingFee > 0 ? currentShippingFee.toString() : '');
        if (shippingInput === null) return; // User cancelled
        
        const shippingFee = parseFloat(shippingInput.replace(/[^\d]/g, '')) || 0;
        if (shippingFee < 0) {
            alert('Phí vận chuyển không hợp lệ.');
            return;
        }
        
        const totalRevenue = codCollected + shippingFee;
        
        // Xác nhận lại
        let confirmMessage = 'Xác nhận thông tin giao hàng:\n\n';
        confirmMessage += `COD đã thu: ${codCollected.toLocaleString('vi-VN')} đ\n`;
        confirmMessage += `Phí vận chuyển: ${shippingFee.toLocaleString('vi-VN')} đ\n`;
        confirmMessage += `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`;
        confirmMessage += `TỔNG DOANH THU: ${totalRevenue.toLocaleString('vi-VN')} đ\n\n`;
        confirmMessage += 'Xác nhận cập nhật trạng thái "Giao hàng thành công"?';
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        data.cod_collected = codCollected;
        data.shipping_fee = shippingFee;
        data.delivery_notes = 'Giao hàng thành công';
    } else {
        // Nhập lý do thất bại
        const failureReason = prompt('Nhập lý do giao hàng thất bại:');
        if (failureReason === null || !failureReason.trim()) {
            alert('Vui lòng nhập lý do giao hàng thất bại.');
            return;
        }
        
        data.failure_reason = failureReason.trim();
        
        if (!confirm(`Xác nhận cập nhật đơn hàng #${orderId} thành "Giao hàng thất bại"?\n\nLý do: ${failureReason}`)) {
            return;
        }
    }
    
    // Gửi request cập nhật trạng thái
    $.ajax({
        url: `/admin/delivery/update-status/${orderId}`,
        method: 'POST',
        data: data,
        success: function(response) {
            if (typeof showNotification === 'function') {
                showNotification('success', response.message || 'Cập nhật trạng thái giao hàng thành công!');
            } else {
                alert(response.message || 'Cập nhật trạng thái giao hàng thành công!');
            }
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        },
        error: function(xhr) {
            let errorMessage = 'Có lỗi xảy ra khi cập nhật trạng thái giao hàng.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            if (typeof showNotification === 'function') {
                showNotification('error', errorMessage);
            } else {
                alert(errorMessage);
            }
        }
    });
}
</script>
@endpush

