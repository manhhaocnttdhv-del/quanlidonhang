@extends('admin.layout')

@section('title', 'Giao Hàng')
@section('page-title', 'Giao Hàng')

@section('content')
<div class="row mb-4">
    <div class="col-md-3">
        <a href="#shippedOutSection" class="text-decoration-none" style="color: inherit;">
            <div class="card text-white bg-warning" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">Đã xuất từ kho</h6>
                    <h3 class="mb-0">{{ $stats['shipped_out'] ?? 0 }}</h3>
                    <small>Phân công đi nơi khác</small>
                    <div class="mt-2">
                        <small><i class="fas fa-arrow-down me-1"></i>Click để xem chi tiết</small>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="#incomingSection" class="text-decoration-none" style="color: inherit;">
            <div class="card text-white bg-success" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">Đang đến kho</h6>
                    <h3 class="mb-0">{{ $stats['incoming'] ?? 0 }}</h3>
                    <small>Nhận nơi khác về</small>
                    <div class="mt-2">
                        <small><i class="fas fa-arrow-down me-1"></i>Click để xem chi tiết</small>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-2">
        <a href="#deliverySection" class="text-decoration-none" style="color: inherit;">
            <div class="card text-white bg-primary" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">Đang giao</h6>
                    <h3 class="mb-0">{{ $stats['out_for_delivery'] ?? 0 }}</h3>
                    <small>Đã phân công tài xế giao</small>
                    <div class="mt-2">
                        <small><i class="fas fa-arrow-down me-1"></i>Click để xem chi tiết</small>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-2">
        <a href="{{ route('admin.delivery.delivered') }}" class="text-decoration-none" style="color: inherit;">
            <div class="card text-white bg-success" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Đã giao hôm nay</h6>
                <h3 class="mb-0">{{ $stats['delivered_today'] ?? 0 }}</h3>
                    <div class="mt-2">
                        <small><i class="fas fa-arrow-right me-1"></i>Xem tất cả</small>
            </div>
        </div>
    </div>
        </a>
    </div>
    <div class="col-md-2">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Thất bại hôm nay</h6>
                <h3 class="mb-0">{{ $stats['failed_today'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4" id="inWarehouseSection">
    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>Đơn hàng trong kho - Chưa phân công</h5>
            <small>Danh sách đơn hàng trong kho chưa được phân công tài xế giao hàng</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" action="{{ route('admin.delivery.index') }}" id="filterProvinceWarehouseForm" class="mb-0 d-inline-block">
                @if(request('province_in_transit'))
                <input type="hidden" name="province_in_transit" value="{{ request('province_in_transit') }}">
                @endif
                @if(request('province_delivery'))
                <input type="hidden" name="province_delivery" value="{{ request('province_delivery') }}">
                @endif
                <select name="province_warehouse" id="filterProvinceWarehouse" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tất cả tỉnh/TP</option>
                    @php
                        // Load tất cả 63 tỉnh từ file JSON
                        $addressesJson = file_get_contents(public_path('data/vietnam-addresses-full.json'));
                        $addresses = json_decode($addressesJson, true);
                        $allProvinces = collect($addresses['provinces'] ?? [])->pluck('name')->sort()->values();
                    @endphp
                    @foreach($allProvinces as $province)
                    <option value="{{ $province }}" {{ request('province_warehouse') == $province ? 'selected' : '' }}>{{ $province }}</option>
                    @endforeach
                </select>
            </form>
            <button type="button" class="btn btn-sm btn-success" id="bulkAssignDeliveryDriverBtn" disabled onclick="return bulkAssignDeliveryDriver();">
                <i class="fas fa-user-plus me-1"></i>Phân công tài xế nhiều đơn (<span id="selectedInWarehouseCount">0</span>)
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="inWarehouseTable">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAllInWarehouse" title="Chọn tất cả">
                        </th>
                        <th>Mã vận đơn</th>
                        <th>Người nhận</th>
                        <th>Địa chỉ giao</th>
                        <th>Tỉnh/TP</th>
                        <th>COD</th>
                        <th>Ngày nhập kho</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ordersInWarehouse ?? [] as $order)
                    <tr>
                        <td>
                            <input type="checkbox" class="order-checkbox-warehouse" value="{{ $order->id }}" 
                                   data-tracking="{{ $order->tracking_number }}">
                        </td>
                        <td><strong>{{ $order->tracking_number }}</strong></td>
                        <td>
                            {{ $order->receiver_name }}<br>
                            <small class="text-muted">{{ $order->receiver_phone }}</small>
                        </td>
                        <td>{{ $order->receiver_address }}</td>
                        <td><span class="badge bg-info">{{ $order->receiver_province }}</span></td>
                        <td>{{ number_format($order->cod_amount) }} đ</td>
                        <td>
                            @php
                                $lastInTransaction = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                                    ->where('type', 'in')
                                    ->orderBy('transaction_date', 'desc')
                                    ->first();
                            @endphp
                            @if($lastInTransaction)
                            <small>{{ $lastInTransaction->transaction_date->format('d/m/Y H:i') }}</small>
                            @elseif($order->picked_up_at)
                            <small>{{ $order->picked_up_at->format('d/m/Y H:i') }}</small>
                            @else
                            <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-success" onclick="assignDeliveryDriver({{ $order->id }})" 
                                        title="Phân công tài xế giao hàng">
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
                        <td colspan="8" class="text-center text-muted">
                            <i class="fas fa-warehouse me-2"></i>Không có đơn hàng nào trong kho chưa phân công
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- PHẦN 1: Đơn hàng đã xuất từ kho này - Phân công đi nơi khác -->
<div class="card mb-4" id="shippedOutSection">
    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-arrow-up me-2"></i>1. Đơn hàng đã xuất từ kho - Phân công đi nơi khác</h5>
            <small>
                @if(isset($userWarehouse) && $userWarehouse)
                    Danh sách đơn hàng đã xuất từ kho {{ $userWarehouse->name }} ({{ $userWarehouse->province ?? '' }}) - Tổng: {{ count($ordersShippedOut ?? []) }} đơn
                @else
                    Danh sách đơn hàng đã xuất từ kho này - Tổng: {{ count($ordersShippedOut ?? []) }} đơn
                @endif
            </small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" action="{{ route('admin.delivery.index') }}" id="filterProvinceShippedOutForm" class="mb-0 d-inline-block">
                @if(request('province_delivery'))
                <input type="hidden" name="province_delivery" value="{{ request('province_delivery') }}">
                @endif
                @if(request('province_incoming'))
                <input type="hidden" name="province_incoming" value="{{ request('province_incoming') }}">
                @endif
                <select name="province_shipped_out" id="filterProvinceShippedOut" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tất cả tỉnh/TP</option>
                    @php
                        // Load tất cả 63 tỉnh từ file JSON
                        $addressesJson = file_get_contents(public_path('data/vietnam-addresses-full.json'));
                        $addresses = json_decode($addressesJson, true);
                        $allProvinces = collect($addresses['provinces'] ?? [])->pluck('name')->sort()->values();
                    @endphp
                    @foreach($allProvinces as $province)
                    <option value="{{ $province }}" {{ request('province_shipped_out') == $province ? 'selected' : '' }}>{{ $province }}</option>
                    @endforeach
                </select>
            </form>
            <button type="button" class="btn btn-sm btn-success" id="bulkAssignDriverShippedOutBtn" disabled onclick="return bulkAssignDriver('shippedOut');">
                <i class="fas fa-user-plus me-1"></i>Phân công tài xế nhiều đơn (<span id="selectedShippedOutCount">0</span>)
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="shippedOutTable">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAllShippedOut" title="Chọn tất cả (chỉ chọn đơn chưa phân công tài xế)">
                        </th>
                        <th>Mã vận đơn</th>
                        <th>Kho gửi</th>
                        <th>Kho nhận</th>
                        <th>Người nhận</th>
                        <th>Địa chỉ giao</th>
                        <th>Tỉnh/TP</th>
                        <th>COD</th>
                        <th>Ngày xuất kho</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ordersShippedOut ?? [] as $order)
                    <tr>
                        <td>
                            @if(!$order->delivery_driver_id)
                                <input type="checkbox" class="order-checkbox-shipped-out" value="{{ $order->id }}" 
                                   data-tracking="{{ $order->tracking_number }}"
                                   data-receiver-province="{{ $order->receiver_province }}"
                                   data-to-warehouse="{{ $order->to_warehouse_id ?? '' }}">
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $order->tracking_number }}</strong>
                            @if($order->delivery_driver_id)
                                <br><small class="badge bg-success mt-1"><i class="fas fa-check me-1"></i>Đã phân công tài xế</small>
                            @else
                                <br><small class="badge bg-warning text-dark mt-1"><i class="fas fa-clock me-1"></i>Chưa phân công</small>
                            @endif
                        </td>
                        <td>
                            @php
                                // Lấy kho gửi từ transaction 'out' đầu tiên từ kho của user hiện tại
                                $fromWarehouse = null;
                                $currentUser = auth()->user();
                                if ($currentUser && $currentUser->isWarehouseAdmin() && $currentUser->warehouse_id) {
                                    $firstOutTransaction = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                                        ->where('warehouse_id', $currentUser->warehouse_id)
                                        ->where('type', 'out')
                                        ->orderBy('transaction_date', 'asc')
                                        ->with('warehouse')
                                        ->first();
                                    
                                    if ($firstOutTransaction && $firstOutTransaction->warehouse) {
                                        $fromWarehouse = $firstOutTransaction->warehouse;
                                    }
                                }
                                
                                // Fallback: nếu không tìm thấy transaction từ kho của user, tìm transaction 'out' đầu tiên
                                if (!$fromWarehouse) {
                                    $firstOutTransaction = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                                        ->where('type', 'out')
                                        ->orderBy('transaction_date', 'asc')
                                        ->with('warehouse')
                                        ->first();
                                    
                                    if ($firstOutTransaction && $firstOutTransaction->warehouse) {
                                        $fromWarehouse = $firstOutTransaction->warehouse;
                                    }
                                }
                                
                                // Fallback cuối cùng: dùng warehouse hiện tại của đơn hàng
                                if (!$fromWarehouse && $order->warehouse) {
                                    $fromWarehouse = $order->warehouse;
                                }
                            @endphp
                            @if($fromWarehouse)
                                <span class="badge bg-secondary">
                                    <i class="fas fa-warehouse me-1"></i>{{ $fromWarehouse->name }}
                                </span><br>
                                <small class="text-muted">{{ $fromWarehouse->province ?? '' }}</small>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @php
                                // Lấy thông tin kho đích
                                $toWarehouse = null;
                                $hasBeenReceived = false;
                                
                                if ($order->to_warehouse_id) {
                                    // Đơn hàng đang vận chuyển, chưa nhận
                                    $toWarehouse = \App\Models\Warehouse::find($order->to_warehouse_id);
                                    $hasBeenReceived = false;
                                } elseif ($order->status === 'in_warehouse') {
                                    // Đơn hàng đã được nhận vào kho
                                    // Kiểm tra xem có transaction nhập kho từ kho khác không
                                    $lastInTransaction = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                                        ->where('type', 'in')
                                        ->orderBy('transaction_date', 'desc')
                                        ->first();
                                    
                                    if ($lastInTransaction && $lastInTransaction->notes && strpos($lastInTransaction->notes, 'Nhận từ') !== false) {
                                        // Đơn hàng đã được nhận từ kho khác
                                        $hasBeenReceived = true;
                                        $toWarehouse = \App\Models\Warehouse::find($order->warehouse_id); // Kho hiện tại là kho đích
                                    }
                                }
                                
                                // Nếu chưa có kho đích nhưng có receiver_province khác với kho gửi, tự động tìm kho đích
                                if (!$toWarehouse && $order->receiver_province && $order->warehouse) {
                                    $fromWarehouse = $order->warehouse;
                                    if ($fromWarehouse->province && $order->receiver_province !== $fromWarehouse->province) {
                                        // Tìm kho đích dựa trên receiver_province (tìm chính xác hoặc tìm trong tên)
                                        $receiverProvince = $order->receiver_province;
                                        $receiverProvinceShort = str_replace(['Thành phố ', 'Tỉnh ', 'TP. ', 'TP '], '', $receiverProvince);
                                        
                                        // Tạo danh sách các biến thể tên tỉnh để tìm kho
                                        $provinceVariants = [
                                            $receiverProvince,
                                            $receiverProvinceShort
                                        ];
                                        
                                        // Thêm các biến thể đặc biệt cho Hồ Chí Minh/Sài Gòn
                                        if (stripos($receiverProvince, 'Hồ Chí Minh') !== false || 
                                            stripos($receiverProvince, 'HCM') !== false ||
                                            stripos($receiverProvince, 'Ho Chi Minh') !== false ||
                                            stripos($receiverProvince, 'Sài Gòn') !== false) {
                                            $provinceVariants[] = 'Thành phố Hồ Chí Minh';
                                            $provinceVariants[] = 'Hồ Chí Minh';
                                            $provinceVariants[] = 'Sài Gòn';
                                            $provinceVariants[] = 'TP. Hồ Chí Minh';
                                            $provinceVariants[] = 'TP.HCM';
                                        }
                                        $provinceVariants = array_unique($provinceVariants);
                                        
                                        // Tìm kho đích: ưu tiên tìm chính xác theo province, sau đó tìm trong tên kho
                                        $toWarehouse = \App\Models\Warehouse::where('is_active', true)
                                            ->where(function($q) use ($provinceVariants) {
                                                foreach ($provinceVariants as $variant) {
                                                    $q->orWhere('province', $variant)
                                                      ->orWhere('province', 'like', '%' . $variant . '%')
                                                      ->orWhere('name', 'like', '%' . $variant . '%');
                                                }
                                            })
                                            ->orderByRaw("CASE 
                                                WHEN province = '" . addslashes($receiverProvince) . "' THEN 0 
                                                WHEN name LIKE '%" . addslashes($receiverProvince) . "%' THEN 1 
                                                WHEN name LIKE '%Sài Gòn%' THEN 2
                                                WHEN province LIKE '%" . addslashes($receiverProvinceShort) . "%' THEN 3
                                                ELSE 4 
                                            END")
                                            ->first();
                                    }
                                }
                                @endphp
                            
                                @if($toWarehouse)
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-arrow-right me-1"></i>{{ $toWarehouse->name }}
                                    </span><br>
                                    <small class="text-muted">{{ $toWarehouse->province ?? '' }}</small>
                                @if($hasBeenReceived)
                                    <br><small class="text-success"><i class="fas fa-check me-1"></i>Đã nhận</small>
                                @else
                                    <br><small class="text-danger"><i class="fas fa-clock me-1"></i>Chưa nhận</small>
                                @endif
                            @else
                                <span class="text-muted">Giao trực tiếp</span>
                            @endif
                        </td>
                        <td>
                            {{ $order->receiver_name }}<br>
                            <small class="text-muted">{{ $order->receiver_phone }}</small>
                        </td>
                        <td>{{ $order->receiver_address }}</td>
                        <td><span class="badge bg-info">{{ $order->receiver_province }}</span></td>
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
                                @if(!$order->delivery_driver_id)
                                <button class="btn btn-sm btn-success" onclick="assignDriver({{ $order->id }}, {{ $order->to_warehouse_id ?? 'null' }})" 
                                        title="{{ $order->to_warehouse_id ? 'Phân công tài xế vận chuyển tỉnh (kho đích chưa nhận được)' : 'Phân công tài xế giao hàng' }}">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                                @else
                                    @if($order->deliveryDriver)
                                        <span class="badge bg-success me-1" title="Tài xế: {{ $order->deliveryDriver->name }}">
                                            <i class="fas fa-user me-1"></i>{{ $order->deliveryDriver->name }}
                                        </span>
                                    @endif
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
                            <i class="fas fa-truck me-2"></i>Không có đơn hàng nào đã xuất từ kho này
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- PHẦN 2: Đơn hàng đang đến kho này - Nhận nơi khác về -->
<div class="card mb-4" id="incomingSection">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-arrow-down me-2"></i>2. Đơn hàng đang đến kho - Nhận nơi khác về</h5>
            <small>
                @if(isset($userWarehouse) && $userWarehouse)
                    Danh sách đơn hàng đang vận chuyển đến kho {{ $userWarehouse->name }} ({{ $userWarehouse->province ?? '' }}) - Tổng: {{ count($ordersIncoming ?? []) }} đơn
                @else
                    Danh sách đơn hàng đang vận chuyển đến kho này - Tổng: {{ count($ordersIncoming ?? []) }} đơn
                @endif
            </small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" action="{{ route('admin.delivery.index') }}" id="filterProvinceIncomingForm" class="mb-0 d-inline-block">
                @if(request('province_delivery'))
                <input type="hidden" name="province_delivery" value="{{ request('province_delivery') }}">
                @endif
                @if(request('province_shipped_out'))
                <input type="hidden" name="province_shipped_out" value="{{ request('province_shipped_out') }}">
                @endif
                <select name="province_incoming" id="filterProvinceIncoming" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tất cả tỉnh/TP</option>
                    @php
                        // Load tất cả 63 tỉnh từ file JSON
                        $addressesJson = file_get_contents(public_path('data/vietnam-addresses-full.json'));
                        $addresses = json_decode($addressesJson, true);
                        $allProvinces = collect($addresses['provinces'] ?? [])->pluck('name')->sort()->values();
                    @endphp
                    @foreach($allProvinces as $province)
                    <option value="{{ $province }}" {{ request('province_incoming') == $province ? 'selected' : '' }}>{{ $province }}</option>
                    @endforeach
                </select>
            </form>
            <button type="button" class="btn btn-sm btn-warning me-2" id="bulkReceiveBtn" disabled onclick="return bulkReceiveOrders();">
                <i class="fas fa-check-circle me-1"></i>Nhận hàng nhiều đơn (<span id="selectedIncomingCount">0</span>)
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Lưu ý:</strong> Đây là các đơn hàng đang được vận chuyển đến kho này. Bạn có thể nhận hàng trực tiếp tại đây hoặc vào trang "Kho" để nhận đơn hàng vào kho.
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="incomingTable">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAllIncoming" title="Chọn tất cả">
                        </th>
                        <th>Mã vận đơn</th>
                        <th>Kho gửi</th>
                        <th>Kho nhận</th>
                        <th>Người nhận</th>
                        <th>Địa chỉ giao</th>
                        <th>Tỉnh/TP</th>
                        <th>COD</th>
                        <th>Ngày xuất kho</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ordersIncoming ?? [] as $order)
                    <tr>
                        <td>
                            <input type="checkbox" class="order-checkbox-incoming" value="{{ $order->id }}" 
                                   data-tracking="{{ $order->tracking_number }}"
                                   data-receiver-province="{{ $order->receiver_province }}"
                                   data-to-warehouse="{{ $order->to_warehouse_id ?? '' }}">
                        </td>
                        <td><strong>{{ $order->tracking_number }}</strong></td>
                        <td>
                            @if($order->warehouse)
                                <span class="badge bg-secondary">
                                    <i class="fas fa-warehouse me-1"></i>{{ $order->warehouse->name }}
                                </span><br>
                                <small class="text-muted">{{ $order->warehouse->province ?? '' }}</small>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @php
                                // Lấy thông tin kho đích
                                $toWarehouse = null;
                                $hasBeenReceived = false;
                                $currentWarehouseId = auth()->user()->warehouse_id ?? null;
                                
                                // Kiểm tra xem đơn hàng đã được nhận vào kho hiện tại chưa
                                // Bằng cách kiểm tra transaction 'in' tại kho hiện tại với notes chứa "Nhận từ"
                                if ($currentWarehouseId) {
                                    $lastInTransaction = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                                        ->where('warehouse_id', $currentWarehouseId)
                                        ->where('type', 'in')
                                        ->orderBy('transaction_date', 'desc')
                                        ->first();
                                    
                                    if ($lastInTransaction && $lastInTransaction->notes && 
                                        (stripos($lastInTransaction->notes, 'Nhận từ') !== false || 
                                         stripos($lastInTransaction->notes, 'kho') !== false)) {
                                        // Đơn hàng đã được nhận vào kho này
                                        $hasBeenReceived = true;
                                        $toWarehouse = \App\Models\Warehouse::find($currentWarehouseId);
                                    }
                                }
                                
                                // Nếu chưa nhận, tìm kho đích
                                if (!$hasBeenReceived) {
                                    if ($order->to_warehouse_id) {
                                        // Đơn hàng đang vận chuyển đến kho này
                                    $toWarehouse = \App\Models\Warehouse::find($order->to_warehouse_id);
                                        // Nếu to_warehouse_id = kho hiện tại, đơn hàng đang đến kho này
                                        if ($toWarehouse && $toWarehouse->id == $currentWarehouseId) {
                                            // Đơn hàng đang đến kho này, chưa nhận
                                            $hasBeenReceived = false;
                                        }
                                    } elseif ($order->status === 'in_warehouse' && $order->warehouse_id == $currentWarehouseId) {
                                        // Đơn hàng đã ở kho này nhưng chưa có transaction 'in' với notes "Nhận từ"
                                        // Có thể là đơn hàng được tạo trực tiếp tại kho này, không phải nhận từ kho khác
                                        $toWarehouse = \App\Models\Warehouse::find($order->warehouse_id);
                                    }
                                    
                                    // Nếu chưa có kho đích nhưng có receiver_province khác với kho gửi, tự động tìm kho đích
                                    if (!$toWarehouse && $order->receiver_province && $order->warehouse) {
                                        $fromWarehouse = $order->warehouse;
                                        if ($fromWarehouse->province && $order->receiver_province !== $fromWarehouse->province) {
                                            // Tìm kho đích dựa trên receiver_province (tìm chính xác hoặc tìm trong tên)
                                            $receiverProvince = $order->receiver_province;
                                            $receiverProvinceShort = str_replace(['Thành phố ', 'Tỉnh ', 'TP. ', 'TP '], '', $receiverProvince);
                                            
                                            // Tạo danh sách các biến thể tên tỉnh để tìm kho
                                            $provinceVariants = [
                                                $receiverProvince,
                                                $receiverProvinceShort
                                            ];
                                            
                                            // Thêm các biến thể đặc biệt cho Hồ Chí Minh/Sài Gòn
                                            if (stripos($receiverProvince, 'Hồ Chí Minh') !== false || 
                                                stripos($receiverProvince, 'HCM') !== false ||
                                                stripos($receiverProvince, 'Ho Chi Minh') !== false ||
                                                stripos($receiverProvince, 'Sài Gòn') !== false) {
                                                $provinceVariants[] = 'Thành phố Hồ Chí Minh';
                                                $provinceVariants[] = 'Hồ Chí Minh';
                                                $provinceVariants[] = 'Sài Gòn';
                                                $provinceVariants[] = 'TP. Hồ Chí Minh';
                                                $provinceVariants[] = 'TP.HCM';
                                            }
                                            $provinceVariants = array_unique($provinceVariants);
                                            
                                            // Tìm kho đích: ưu tiên tìm chính xác theo province, sau đó tìm trong tên kho
                                            $toWarehouse = \App\Models\Warehouse::where('is_active', true)
                                                ->where(function($q) use ($provinceVariants) {
                                                    foreach ($provinceVariants as $variant) {
                                                        $q->orWhere('province', $variant)
                                                          ->orWhere('province', 'like', '%' . $variant . '%')
                                                          ->orWhere('name', 'like', '%' . $variant . '%');
                                                    }
                                                })
                                                ->orderByRaw("CASE 
                                                    WHEN province = '" . addslashes($receiverProvince) . "' THEN 0 
                                                    WHEN name LIKE '%" . addslashes($receiverProvince) . "%' THEN 1 
                                                    WHEN name LIKE '%Sài Gòn%' THEN 2
                                                    WHEN province LIKE '%" . addslashes($receiverProvinceShort) . "%' THEN 3
                                                    ELSE 4 
                                                END")
                                                ->first();
                                            
                                            // Nếu tìm thấy kho đích và đơn hàng đã ở kho đó, kiểm tra lại xem đã nhận chưa
                                            if ($toWarehouse && $toWarehouse->id == $currentWarehouseId) {
                                                $lastInTransaction = \App\Models\WarehouseTransaction::where('order_id', $order->id)
                                                    ->where('warehouse_id', $currentWarehouseId)
                                                    ->where('type', 'in')
                                                    ->orderBy('transaction_date', 'desc')
                                                    ->first();
                                                
                                                if ($lastInTransaction && $lastInTransaction->notes && 
                                                    (stripos($lastInTransaction->notes, 'Nhận từ') !== false || 
                                                     stripos($lastInTransaction->notes, 'kho') !== false)) {
                                                    $hasBeenReceived = true;
                                                }
                                            }
                                        }
                                    }
                                }
                                @endphp
                            
                                @if($toWarehouse)
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-arrow-right me-1"></i>{{ $toWarehouse->name }}
                                    </span><br>
                                    <small class="text-muted">{{ $toWarehouse->province ?? '' }}</small>
                                @if($hasBeenReceived)
                                    <br><small class="text-success"><i class="fas fa-check me-1"></i>Đã nhận</small>
                                @else
                                    <br><small class="text-danger"><i class="fas fa-clock me-1"></i>Chưa nhận</small>
                                @endif
                            @else
                                <span class="text-muted">Giao trực tiếp</span>
                            @endif
                        </td>
                        <td>
                            {{ $order->receiver_name }}<br>
                            <small class="text-muted">{{ $order->receiver_phone }}</small>
                        </td>
                        <td>{{ $order->receiver_address }}</td>
                        <td><span class="badge bg-info">{{ $order->receiver_province }}</span></td>
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
                                @php
                                    // Chỉ hiển thị nút khi đơn hàng chưa được nhận
                                    // Nếu đã nhận, ẩn cả nút "Nhận hàng" và "Phân công tài xế"
                                @endphp
                                @if(!$hasBeenReceived && $order->delivery_driver_id)
                                    {{-- Chỉ hiển thị nút "Nhận hàng" khi đã được phân công tài xế --}}
                                    <button class="btn btn-sm btn-warning" onclick="receiveOrder({{ $order->id }}, {{ $order->warehouse_id ?? 'null' }})" 
                                            title="Nhận đơn hàng vào kho">
                                        <i class="fas fa-check-circle me-1"></i>Nhận hàng
                                </button>
                                @elseif(!$hasBeenReceived && !$order->delivery_driver_id)
                                    {{-- Chưa được phân công tài xế - hiển thị thông báo --}}
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>Chờ kho gửi phân công tài xế
                                    </small>
                                @else
                                    {{-- Đơn hàng đã nhận - chỉ hiển thị badge nếu đã có tài xế, không hiển thị nút phân công --}}
                                    @if($order->delivery_driver_id && $order->deliveryDriver)
                                        <span class="badge bg-success me-1" title="Tài xế: {{ $order->deliveryDriver->name }}">
                                            <i class="fas fa-user me-1"></i>{{ $order->deliveryDriver->name }}
                                        </span>
                                    @else
                                        <span class="text-muted small">
                                            <i class="fas fa-check-circle me-1 text-success"></i>Đã nhận
                                        </span>
                                    @endif
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
                            <i class="fas fa-truck-loading me-2"></i>Không có đơn hàng nào đang đến kho này
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- PHẦN 3: Đơn hàng đã nhận từ kho khác -->
<div class="card mb-4" id="receivedSection">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>3. Đơn hàng đã nhận từ kho khác</h5>
            <small>
                @if(isset($userWarehouse) && $userWarehouse)
                    Danh sách đơn hàng đã nhận vào kho {{ $userWarehouse->name }} ({{ $userWarehouse->province ?? '' }}) - Tổng: {{ count($ordersReceivedFromWarehouses ?? []) }} đơn
                @else
                    Danh sách đơn hàng đã nhận từ kho khác - Tổng: {{ count($ordersReceivedFromWarehouses ?? []) }} đơn
                @endif
            </small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" action="{{ route('admin.delivery.index') }}" id="filterProvinceReceivedForm" class="mb-0 d-inline-block">
                @if(request('province_delivery'))
                <input type="hidden" name="province_delivery" value="{{ request('province_delivery') }}">
                @endif
                @if(request('province_shipped_out'))
                <input type="hidden" name="province_shipped_out" value="{{ request('province_shipped_out') }}">
                @endif
                @if(request('province_incoming'))
                <input type="hidden" name="province_incoming" value="{{ request('province_incoming') }}">
                @endif
                <select name="province_received" id="filterProvinceReceived" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tất cả tỉnh/TP</option>
                    @php
                        // Load tất cả 63 tỉnh từ file JSON
                        $addressesJson = file_get_contents(public_path('data/vietnam-addresses-full.json'));
                        $addresses = json_decode($addressesJson, true);
                        $allProvinces = collect($addresses['provinces'] ?? [])->pluck('name')->sort()->values();
                    @endphp
                    @foreach($allProvinces as $province)
                    <option value="{{ $province }}" {{ request('province_received') == $province ? 'selected' : '' }}>{{ $province }}</option>
                    @endforeach
                </select>
            </form>
            <button type="button" class="btn btn-sm btn-success" id="bulkAssignDriverReceivedBtn" disabled onclick="return bulkAssignDriver('received');">
                <i class="fas fa-user-plus me-1"></i>Phân công tài xế nhiều đơn (<span id="selectedReceivedCount">0</span>)
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Lưu ý:</strong> Đây là các đơn hàng đã được nhận vào kho từ kho khác. Bạn có thể phân công tài xế shipper để giao hàng đến khách hàng hoặc cập nhật trạng thái giao hàng.
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="receivedTable">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAllReceived" title="Chọn tất cả (chỉ chọn đơn chưa phân công tài xế)">
                        </th>
                        <th>Mã vận đơn</th>
                        <th>Kho gửi</th>
                        <th>Người nhận</th>
                        <th>Địa chỉ giao</th>
                        <th>Tỉnh/TP</th>
                        <th>COD</th>
                        <th>Ngày nhận</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ordersReceivedFromWarehouses ?? [] as $order)
                    <tr>
                        <td>
                            @if(!$order->delivery_driver_id)
                                <input type="checkbox" class="order-checkbox-received" value="{{ $order->id }}" 
                                   data-tracking="{{ $order->tracking_number }}"
                                   data-receiver-province="{{ $order->receiver_province }}">
                            @endif
                        </td>
                        <td><strong>{{ $order->tracking_number }}</strong></td>
                        <td>
                            @php
                                $lastInTransaction = \App\Models\WarehouseTransaction::where('warehouse_id', $order->warehouse_id)
                                    ->where('order_id', $order->id)
                                    ->where('type', 'in')
                                    ->where(function($q) {
                                        $q->where('notes', 'like', '%Nhận từ%')
                                          ->orWhere('notes', 'like', '%Nhận từ kho%')
                                          ->orWhere('notes', 'like', '%từ kho%');
                                    })
                                    ->orderBy('transaction_date', 'desc')
                                    ->first();
                                $fromWarehouseName = 'Kho khác';
                                if ($lastInTransaction && $lastInTransaction->notes) {
                                    if (preg_match('/từ\s+(.+?)(\s*\(|$)/i', $lastInTransaction->notes, $matches)) {
                                        $fromWarehouseName = $matches[1];
                                    }
                                }
                            @endphp
                            <span class="badge bg-secondary">
                                <i class="fas fa-warehouse me-1"></i>{{ $fromWarehouseName }}
                            </span>
                        </td>
                        <td>
                            {{ $order->receiver_name }}<br>
                            <small class="text-muted">{{ $order->receiver_phone }}</small>
                        </td>
                        <td>{{ $order->receiver_address }}</td>
                        <td><span class="badge bg-info">{{ $order->receiver_province }}</span></td>
                        <td>{{ number_format($order->cod_amount) }} đ</td>
                        <td>
                            @if($lastInTransaction)
                            <small>{{ $lastInTransaction->transaction_date->format('d/m/Y H:i') }}</small>
                            @else
                            <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($order->status === 'in_warehouse')
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-box me-1"></i>Trong kho
                                </span>
                            @elseif($order->status === 'out_for_delivery')
                                <span class="badge bg-primary">
                                    <i class="fas fa-shipping-fast me-1"></i>Đang giao
                                </span>
                            @endif
                            @if($order->delivery_driver_id)
                                <br><small class="badge bg-success mt-1"><i class="fas fa-check me-1"></i>Đã phân công</small>
                            @else
                                <br><small class="badge bg-warning text-dark mt-1"><i class="fas fa-clock me-1"></i>Chưa phân công</small>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                @if(!$order->delivery_driver_id)
                                <button class="btn btn-sm btn-success" onclick="assignDriver({{ $order->id }}, null)" 
                                        title="Phân công tài xế shipper để giao hàng">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                                @else
                                    @if($order->deliveryDriver)
                                        <span class="badge bg-success me-1" title="Tài xế: {{ $order->deliveryDriver->name }}">
                                            <i class="fas fa-user me-1"></i>{{ $order->deliveryDriver->name }}
                                        </span>
                                    @endif
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
                            <i class="fas fa-check-circle me-2"></i>Không có đơn hàng nào đã nhận từ kho khác
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card" id="deliverySection">
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
                    <div id="orderToWarehouseWarning" class="alert alert-warning d-none">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Lưu ý:</strong> Đơn hàng này đang vận chuyển đến kho khác. 
                        <strong>Kho đích chưa nhận được hàng.</strong> 
                        Chỉ có thể phân công <strong>tài xế vận chuyển tỉnh</strong> để vận chuyển đến kho đích.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Chọn tài xế <span class="text-danger">*</span></label>
                        <select name="driver_id" class="form-select" required id="driverSelect">
                            <option value="">-- Chọn tài xế --</option>
                            @foreach($drivers ?? [] as $driver)
                            <option value="{{ $driver->id }}" 
                                    data-driver-type="{{ $driver->driver_type }}">
                                {{ $driver->name }} - {{ $driver->phone }}
                                @if($driver->driver_type === 'intercity_driver')
                                    <span class="badge bg-warning">Vận chuyển tỉnh</span>
                                @elseif($driver->driver_type === 'shipper')
                                    <span class="badge bg-info">Shipper</span>
                                @endif
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted" id="driverSelectHint"></small>
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

@push('styles')
<style>
    html {
        scroll-behavior: smooth;
    }
    .card[style*="cursor: pointer"]:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Xử lý chọn nhiều đơn hàng đã xuất từ kho (Phần 1)
    function updateSelectedShippedOutCount() {
        const selected = $('.order-checkbox-shipped-out:checked').map(function() {
            return $(this).val();
        }).get();
        
        const count = selected.length;
        $('#selectedShippedOutCount').text(count);
        $('#bulkAssignDriverShippedOutBtn').prop('disabled', count === 0);
    }
    
    // Xử lý chọn nhiều đơn hàng đang đến kho (Phần 2)
    function updateSelectedIncomingCount() {
        // Chỉ đếm các đơn hàng chưa nhận (không có status "Đã nhận")
        const selected = [];
        $('.order-checkbox-incoming:checked').each(function() {
            const row = $(this).closest('tr');
            // Kiểm tra xem đơn hàng đã nhận chưa (dựa vào text "Đã nhận" trong cột Kho nhận)
            const khoNhanCell = row.find('td').eq(3); // Cột "Kho nhận" là cột thứ 4 (index 3)
            const hasReceived = khoNhanCell.find('.text-success, .fa-check').length > 0;
            if (!hasReceived) {
                selected.push($(this).val());
            }
        });
        
        const count = selected.length;
        $('#selectedIncomingCount').text(count);
        $('#bulkReceiveBtn').prop('disabled', count === 0);
    }
    
    function updateSelectedReceivedCount() {
        const selected = $('.order-checkbox-received:checked').map(function() {
            return $(this).val();
        }).get();
        
        const count = selected.length;
        $('#selectedReceivedCount').text(count);
        $('#bulkAssignDriverReceivedBtn').prop('disabled', count === 0);
    }
    
    // DataTable cho bảng đơn hàng đã xuất từ kho (Phần 1)
    @if(isset($ordersShippedOut) && count($ordersShippedOut) > 0)
    var shippedOutTable = $('#shippedOutTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[8, 'desc']],
        drawCallback: function() {
            $('.order-checkbox-shipped-out').off('change').on('change', function() {
                updateSelectedShippedOutCount();
                const total = $('.order-checkbox-shipped-out').length;
                const checked = $('.order-checkbox-shipped-out:checked').length;
                $('#selectAllShippedOut').prop('checked', total === checked && total > 0);
            });
        }
    });
    
    $(document).on('change', '#selectAllShippedOut', function() {
        const isChecked = this.checked;
        $('.order-checkbox-shipped-out').prop('checked', isChecked);
        updateSelectedShippedOutCount();
    });
    @else
    $('#selectAllShippedOut').on('change', function() {
        $('.order-checkbox-shipped-out').prop('checked', this.checked);
        updateSelectedShippedOutCount();
    });
    
    $('.order-checkbox-shipped-out').on('change', function() {
        updateSelectedShippedOutCount();
        const total = $('.order-checkbox-shipped-out').length;
        const checked = $('.order-checkbox-shipped-out:checked').length;
        $('#selectAllShippedOut').prop('checked', total === checked && total > 0);
    });
    @endif
    
    // DataTable cho bảng đơn hàng đang đến kho (Phần 2)
    @if(isset($ordersIncoming) && count($ordersIncoming) > 0)
    var incomingTable = $('#incomingTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[8, 'desc']],
        drawCallback: function() {
            $('.order-checkbox-incoming').off('change').on('change', function() {
                updateSelectedIncomingCount();
                const total = $('.order-checkbox-incoming').length;
                const checked = $('.order-checkbox-incoming:checked').length;
                $('#selectAllIncoming').prop('checked', total === checked && total > 0);
            });
        }
    });
    
    $(document).on('change', '#selectAllIncoming', function() {
        const isChecked = this.checked;
        $('.order-checkbox-incoming').prop('checked', isChecked);
        updateSelectedIncomingCount();
    });
    @else
    $('#selectAllIncoming').on('change', function() {
        $('.order-checkbox-incoming').prop('checked', this.checked);
        updateSelectedIncomingCount();
    });
    
    $('.order-checkbox-incoming').on('change', function() {
        updateSelectedIncomingCount();
        const total = $('.order-checkbox-incoming').length;
        const checked = $('.order-checkbox-incoming:checked').length;
        $('#selectAllIncoming').prop('checked', total === checked && total > 0);
    });
    @endif
    
    // Trigger update on page load
    updateSelectedShippedOutCount();
    updateSelectedIncomingCount();
    updateSelectedReceivedCount();
    
    // Xử lý checkbox cho phần "Đơn hàng đã nhận từ kho khác"
    $(document).on('change', '#selectAllReceived', function() {
        const isChecked = this.checked;
        $('.order-checkbox-received').prop('checked', isChecked);
        updateSelectedReceivedCount();
    });
    
    $(document).on('change', '.order-checkbox-received', function() {
        updateSelectedReceivedCount();
        const total = $('.order-checkbox-received').length;
        const checked = $('.order-checkbox-received:checked').length;
        $('#selectAllReceived').prop('checked', total === checked && total > 0);
    });
    
    // Xử lý chọn nhiều đơn hàng trong kho
    function updateSelectedInWarehouseCount() {
        const selected = $('.order-checkbox-warehouse:checked').map(function() {
            return $(this).val();
        }).get();
        
        const count = selected.length;
        $('#selectedInWarehouseCount').text(count);
        $('#bulkAssignDeliveryDriverBtn').prop('disabled', count === 0);
    }
    
    // Xử lý chọn tất cả đơn hàng trong kho
    $('#selectAllInWarehouse').on('change', function() {
        $('.order-checkbox-warehouse').prop('checked', this.checked);
        updateSelectedInWarehouseCount();
    });
    
    $('.order-checkbox-warehouse').on('change', function() {
        updateSelectedInWarehouseCount();
        const total = $('.order-checkbox-warehouse').length;
        const checked = $('.order-checkbox-warehouse:checked').length;
        $('#selectAllInWarehouse').prop('checked', total === checked && total > 0);
    });
    
    // Trigger update on page load
    updateSelectedInWarehouseCount();
    
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

function assignDriver(orderId, toWarehouseId) {
    console.log('assignDriver called', { orderId, toWarehouseId });
    
    $('#orderId').val(orderId);
    // Sử dụng route name thay vì hardcoded URL
    const assignUrl = '{{ route("admin.delivery.assign-driver", ":id") }}'.replace(':id', orderId);
    console.log('Setting form action to:', assignUrl);
    $('#assignDriverForm').attr('action', assignUrl);
    
    // Kiểm tra nếu đơn hàng đang vận chuyển đến kho khác
    if (toWarehouseId && toWarehouseId !== null && toWarehouseId !== 'null') {
        $('#orderToWarehouseWarning').removeClass('d-none');
        $('#driverSelectHint').html('<strong>Lưu ý:</strong> Chỉ chọn <strong>tài xế vận chuyển tỉnh</strong> vì kho đích chưa nhận được hàng.');
        
        // Lọc chỉ hiển thị tài xế vận chuyển tỉnh
        $('#driverSelect option').each(function() {
            const driverType = $(this).data('driver-type');
            if (driverType !== 'intercity_driver' && $(this).val() !== '') {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    } else {
        $('#orderToWarehouseWarning').addClass('d-none');
        $('#driverSelectHint').text('Chọn tài xế để giao hàng cho khách hàng.');
        
        // Hiển thị tất cả tài xế
        $('#driverSelect option').show();
    }
    
    // Reset selection
    $('#driverSelect').val('');
    
    $('#assignDriverModal').modal('show');
}

// Xử lý submit form phân công tài xế
// Sử dụng $(document).on() để đảm bảo event handler được attach ngay cả khi form được tạo động
$(document).ready(function() {
    console.log('Document ready - attaching form submit handler');
    
    $(document).on('submit', '#assignDriverForm', function(e) {
        e.preventDefault();
        console.log('Form submit event triggered');
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Debug: log action URL
        const actionUrl = form.attr('action');
        const formData = form.serialize();
        console.log('Form action:', actionUrl);
        console.log('Form data:', formData);
        
        if (!actionUrl) {
            console.error('Form action is empty!');
            alert('Lỗi: Không tìm thấy URL để gửi request. Vui lòng thử lại.');
            return false;
        }
        
        // Disable button và hiển thị loading
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Đang xử lý...');
        
        // Gửi request AJAX
        $.ajax({
            url: actionUrl,
            method: 'POST',
            data: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                console.log('Success response:', response);
                // Hiển thị thông báo thành công
                if (typeof showNotification === 'function') {
                    showNotification('success', response.message || 'Đã phân công tài xế thành công!');
                } else {
                    alert(response.message || 'Đã phân công tài xế thành công!');
                }
                
                // Đóng modal
                $('#assignDriverModal').modal('hide');
                
                // Reload trang sau 1 giây
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            },
            error: function(xhr) {
                console.error('Error response:', xhr);
                // Hiển thị lỗi
                let errorMessage = 'Có lỗi xảy ra khi phân công tài xế.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    // Hiển thị lỗi validation
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('\n');
                } else if (xhr.status === 0) {
                    errorMessage = 'Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng.';
                } else if (xhr.status === 404) {
                    errorMessage = 'Không tìm thấy route. Vui lòng kiểm tra lại.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Lỗi server. Vui lòng thử lại sau.';
                }
                
                if (typeof showNotification === 'function') {
                    showNotification('error', errorMessage);
                } else {
                    alert(errorMessage);
                }
                
                // Khôi phục button
                submitBtn.prop('disabled', false);
                submitBtn.html(originalText);
            }
        });
    });
    
    // Xử lý submit form phân công tài xế nhiều đơn
    $(document).on('submit', '#bulkAssignDriverForm', function(e) {
        e.preventDefault();
        console.log('Bulk assign form submit event triggered');
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Debug: log action URL và data
        const actionUrl = form.attr('action');
        const formData = form.serialize();
        console.log('Bulk form action:', actionUrl);
        console.log('Bulk form data:', formData);
        
        if (!actionUrl) {
            console.error('Bulk form action is empty!');
            alert('Lỗi: Không tìm thấy URL để gửi request. Vui lòng thử lại.');
            return false;
        }
        
        // Kiểm tra xem có order_ids không
        const orderIds = form.find('input[name="order_ids[]"]').map(function() {
            return $(this).val();
        }).get();
        
        if (orderIds.length === 0) {
            alert('Vui lòng chọn ít nhất một đơn hàng để phân công tài xế.');
            return false;
        }
        
        // Disable button và hiển thị loading
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Đang xử lý...');
        
        // Gửi request AJAX
        $.ajax({
            url: actionUrl,
            method: 'POST',
            data: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                console.log('Bulk assign success response:', response);
                // Hiển thị thông báo thành công
                if (typeof showNotification === 'function') {
                    showNotification('success', response.message || `Đã phân công tài xế cho ${orderIds.length} đơn hàng thành công!`);
                } else {
                    alert(response.message || `Đã phân công tài xế cho ${orderIds.length} đơn hàng thành công!`);
                }
                
                // Đóng modal
                $('#bulkAssignDriverModal').modal('hide');
                
                // Reload trang sau 1 giây
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            },
            error: function(xhr) {
                console.error('Bulk assign error response:', xhr);
                // Hiển thị lỗi
                let errorMessage = 'Có lỗi xảy ra khi phân công tài xế.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    // Hiển thị lỗi validation
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('\n');
                } else if (xhr.status === 0) {
                    errorMessage = 'Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng.';
                } else if (xhr.status === 404) {
                    errorMessage = 'Không tìm thấy route. Vui lòng kiểm tra lại.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Lỗi server. Vui lòng thử lại sau.';
                }
                
                if (typeof showNotification === 'function') {
                    showNotification('error', errorMessage);
                } else {
                    alert(errorMessage);
                }
                
                // Khôi phục button
                submitBtn.prop('disabled', false);
                submitBtn.html(originalText);
            }
        });
    });
});

function receiveOrder(orderId, fromWarehouseId) {
    if (!confirm('Bạn có chắc chắn muốn nhận đơn hàng này vào kho?')) {
        return;
    }
    
    // Hiển thị loading
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang xử lý...';
    
    // Gọi API nhận hàng
    $.ajax({
        url: '{{ route("admin.warehouses.receive-order") }}',
        method: 'POST',
        data: {
            order_id: orderId,
            from_warehouse_id: fromWarehouseId || null,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            // Hiển thị thông báo thành công
            if (typeof showNotification === 'function') {
                showNotification('success', response.message || 'Đơn hàng đã được nhận vào kho thành công!');
            } else {
                alert(response.message || 'Đơn hàng đã được nhận vào kho thành công!');
            }
            
            // Reload trang để cập nhật trạng thái
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        },
        error: function(xhr) {
            // Hiển thị lỗi
            let errorMessage = 'Có lỗi xảy ra khi nhận đơn hàng.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            if (typeof showNotification === 'function') {
                showNotification('error', errorMessage);
            } else {
                alert(errorMessage);
            }
            
            // Khôi phục nút
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
}

function bulkReceiveOrders() {
    // Lấy tất cả checkbox đã checked và chưa nhận
    const selected = [];
    $('.order-checkbox-incoming:checked').each(function() {
        const row = $(this).closest('tr');
        // Kiểm tra xem đơn hàng đã nhận chưa
        const khoNhanCell = row.find('td').eq(3); // Cột "Kho nhận"
        const hasReceived = khoNhanCell.find('.text-success, .fa-check').length > 0;
        if (!hasReceived) {
            selected.push($(this).val());
        }
    });
    
    if (selected.length === 0) {
        alert('Vui lòng chọn ít nhất một đơn hàng để nhận hàng.');
        return false;
    }
    
    if (!confirm(`Bạn có chắc chắn muốn nhận ${selected.length} đơn hàng vào kho?`)) {
        return false;
    }
    
    // Hiển thị loading
    const btn = $('#bulkReceiveBtn');
    const originalHtml = btn.html();
    btn.prop('disabled', true);
    btn.html('<i class="fas fa-spinner fa-spin me-1"></i>Đang xử lý...');
    
    // Gọi API nhận hàng nhiều đơn
    $.ajax({
        url: '{{ route("admin.warehouses.bulk-receive-order") }}',
        method: 'POST',
        data: {
            order_ids: selected,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            // Hiển thị thông báo thành công
            if (typeof showNotification === 'function') {
                showNotification('success', response.message || `Đã nhận ${selected.length} đơn hàng vào kho thành công!`);
            } else {
                alert(response.message || `Đã nhận ${selected.length} đơn hàng vào kho thành công!`);
            }
            
            // Reload trang để cập nhật trạng thái
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        },
        error: function(xhr) {
            // Hiển thị lỗi
            let errorMessage = 'Có lỗi xảy ra khi nhận đơn hàng.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            if (typeof showNotification === 'function') {
                showNotification('error', errorMessage);
            } else {
                alert(errorMessage);
            }
            
            // Khôi phục nút
            btn.prop('disabled', false);
            btn.html(originalHtml);
        }
    });
}

function bulkAssignDriver(section) {
    console.log('bulkAssignDriver called for section:', section);
    
    // Xác định class checkbox dựa trên section
    let checkboxClass = '';
    if (section === 'shippedOut') {
        checkboxClass = '.order-checkbox-shipped-out';
    } else if (section === 'incoming') {
        checkboxClass = '.order-checkbox-incoming';
    } else if (section === 'received') {
        checkboxClass = '.order-checkbox-received';
    } else {
        // Fallback: dùng cả 3
        checkboxClass = '.order-checkbox-shipped-out, .order-checkbox-incoming, .order-checkbox-received';
    }
    
    // Lấy tất cả checkbox đã checked (kể cả trong DataTable)
    const selected = [];
    $(checkboxClass + ':checked').each(function() {
        selected.push($(this).val());
    });
    
    console.log('Selected orders:', selected);
    
    if (selected.length === 0) {
        alert('Vui lòng chọn ít nhất một đơn hàng để phân công tài xế.');
        return false;
    }
    
    // Lấy thông tin các tỉnh nhận của đơn hàng đã chọn
    const provinces = [];
    $(checkboxClass + ':checked').each(function() {
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

// Phân công tài xế giao hàng cho đơn hàng trong kho
function assignDeliveryDriver(orderId) {
    $('#orderId').val(orderId);
    $('#assignDriverForm').attr('action', '/admin/delivery/assign-driver/' + orderId);
    
    // Đơn hàng trong kho, chỉ cần tài xế shipper
    $('#driverSelectHint').text('Chọn tài xế shipper để giao hàng cho khách hàng');
    $('#assignDriverModal').modal('show');
}

// Phân công tài xế giao hàng nhiều đơn trong kho
function bulkAssignDeliveryDriver() {
    const selected = $('.order-checkbox-warehouse:checked').map(function() {
        return $(this).val();
    }).get();
    
    if (selected.length === 0) {
        alert('Vui lòng chọn ít nhất một đơn hàng để phân công tài xế.');
        return false;
    }
    
    // Xóa các hidden input cũ
    $('#bulkOrderIdsContainer').empty();
    
    // Thêm các order ID vào form
    selected.forEach(function(orderId) {
        $('#bulkOrderIdsContainer').append(`<input type="hidden" name="order_ids[]" value="${orderId}">`);
    });
    
    // Cập nhật thông tin
    $('#bulkSelectedCount').text(selected.length);
    $('#bulkAssignInfo').html(`Bạn đang phân công tài xế cho <strong>${selected.length}</strong> đơn hàng trong kho.`);
    
    // Set action cho form
    $('#bulkAssignDriverForm').attr('action', '{{ route("admin.delivery.bulk-assign-driver") }}');
    
    $('#bulkAssignDriverModal').modal('show');
    return false;
}
</script>
@endpush

