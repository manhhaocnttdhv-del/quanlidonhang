@extends('admin.layout')

@section('title', 'Giao Hàng')
@section('page-title', 'Giao Hàng')

@section('content')
    {{-- <div class="row mb-4">
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
    <div class="col-md-4">
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
    <div class="col-md-3">
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
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Thất bại hôm nay</h6>
                <h3 class="mb-0">{{ $stats['failed_today'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
</div> --}}

    <!-- PHẦN 1: Đơn hàng đã xuất từ kho này - Phân công đi nơi khác -->
    <div class="card mb-4" id="shippedOutSection">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><i class="fas fa-arrow-up me-2"></i>1. Đơn hàng đã xuất từ kho - Phân công đi nơi khác</h5>
                <small>
                    @if (isset($userWarehouse) && $userWarehouse)
                        Danh sách đơn hàng đã xuất từ kho {{ $userWarehouse->name }} ({{ $userWarehouse->province ?? '' }})
                        - Tổng: {{ count($ordersShippedOut ?? []) }} đơn
                    @else
                        Danh sách đơn hàng đã xuất từ kho này - Tổng: {{ count($ordersShippedOut ?? []) }} đơn
                    @endif
                </small>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <form method="GET" action="{{ route('admin.delivery.index') }}" id="filterProvinceShippedOutForm"
                    class="mb-0 d-inline-block">
                    @if (request('province_delivery'))
                        <input type="hidden" name="province_delivery" value="{{ request('province_delivery') }}">
                    @endif
                    @if (request('province_incoming'))
                        <input type="hidden" name="province_incoming" value="{{ request('province_incoming') }}">
                    @endif
                    <select name="province_shipped_out" id="filterProvinceShippedOut" class="form-select form-select-sm"
                        onchange="this.form.submit()">
                        <option value="">Tất cả tỉnh/TP</option>
                        @foreach ($provinces ?? [] as $province)
                            <option value="{{ $province }}"
                                {{ request('province_shipped_out') == $province ? 'selected' : '' }}>{{ $province }}
                            </option>
                        @endforeach
                    </select>
                </form>
                <button type="button" class="btn btn-sm btn-success" id="bulkAssignDriverShippedOutBtn" disabled
                    onclick="return bulkAssignDriver('shippedOut');">
                    <i class="fas fa-user-plus me-1"></i>Phân công tài xế nhiều đơn (<span
                        id="selectedShippedOutCount">0</span>)
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="shippedOutTable">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="selectAllShippedOut"
                                    title="Chọn tất cả (chỉ chọn đơn chưa phân công tài xế)">
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
                                    @if (!$order->delivery_driver_id)
                                        <input type="checkbox" class="order-checkbox-shipped-out"
                                            value="{{ $order->id }}" data-tracking="{{ $order->tracking_number }}"
                                            data-receiver-province="{{ $order->receiver_province }}"
                                            data-to-warehouse="{{ $order->to_warehouse_id ?? '' }}">
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $order->tracking_number }}</strong>
                                    @if ($order->delivery_driver_id)
                                        <br><small class="badge bg-success mt-1"><i class="fas fa-check me-1"></i>Đã phân
                                            công tài xế</small>
                                    @else
                                        <br><small class="badge bg-warning text-dark mt-1"><i
                                                class="fas fa-clock me-1"></i>Chưa phân công</small>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        // Lấy kho gửi từ transaction 'out' đầu tiên từ kho của user hiện tại
                                        $fromWarehouse = null;
                                        $currentUser = auth()->user();
                                        if (
                                            $currentUser &&
                                            $currentUser->isWarehouseAdmin() &&
                                            $currentUser->warehouse_id
                                        ) {
                                            $firstOutTransaction = \App\Models\WarehouseTransaction::where(
                                                'order_id',
                                                $order->id,
                                            )
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
                                            $firstOutTransaction = \App\Models\WarehouseTransaction::where(
                                                'order_id',
                                                $order->id,
                                            )
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
                                    @if ($fromWarehouse)
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
                                            $lastInTransaction = \App\Models\WarehouseTransaction::where(
                                                'order_id',
                                                $order->id,
                                            )
                                                ->where('type', 'in')
                                                ->orderBy('transaction_date', 'desc')
                                                ->first();

                                            if (
                                                $lastInTransaction &&
                                                $lastInTransaction->notes &&
                                                strpos($lastInTransaction->notes, 'Nhận từ') !== false
                                            ) {
                                                // Đơn hàng đã được nhận từ kho khác
                                                $hasBeenReceived = true;
                                                $toWarehouse = \App\Models\Warehouse::find($order->warehouse_id); // Kho hiện tại là kho đích
                                            }
                                        }

                                        // Nếu chưa có kho đích nhưng có receiver_province khác với kho gửi, tự động tìm kho đích
                                        if (!$toWarehouse && $order->receiver_province && $order->warehouse) {
                                            $fromWarehouse = $order->warehouse;
                                            if (
                                                $fromWarehouse->province &&
                                                $order->receiver_province !== $fromWarehouse->province
                                            ) {
                                                // Tìm kho đích dựa trên receiver_province (tìm chính xác hoặc tìm trong tên)
                                                $receiverProvince = $order->receiver_province;
                                                $receiverProvinceShort = str_replace(
                                                    ['Thành phố ', 'Tỉnh ', 'TP. ', 'TP '],
                                                    '',
                                                    $receiverProvince,
                                                );

                                                // Tạo danh sách các biến thể tên tỉnh để tìm kho
                                                $provinceVariants = [$receiverProvince, $receiverProvinceShort];

                                                // Thêm các biến thể đặc biệt cho Hồ Chí Minh/Sài Gòn
                                                if (
                                                    stripos($receiverProvince, 'Hồ Chí Minh') !== false ||
                                                    stripos($receiverProvince, 'HCM') !== false ||
                                                    stripos($receiverProvince, 'Ho Chi Minh') !== false ||
                                                    stripos($receiverProvince, 'Sài Gòn') !== false
                                                ) {
                                                    $provinceVariants[] = 'Thành phố Hồ Chí Minh';
                                                    $provinceVariants[] = 'Hồ Chí Minh';
                                                    $provinceVariants[] = 'Sài Gòn';
                                                    $provinceVariants[] = 'TP. Hồ Chí Minh';
                                                    $provinceVariants[] = 'TP.HCM';
                                                }
                                                $provinceVariants = array_unique($provinceVariants);

                                                // Tìm kho đích: ưu tiên tìm chính xác theo province, sau đó tìm trong tên kho
                                                $toWarehouse = \App\Models\Warehouse::where('is_active', true)
                                                    ->where(function ($q) use ($provinceVariants) {
                                                        foreach ($provinceVariants as $variant) {
                                                            $q->orWhere('province', $variant)
                                                                ->orWhere('province', 'like', '%' . $variant . '%')
                                                                ->orWhere('name', 'like', '%' . $variant . '%');
                                                        }
                                                    })
                                                    ->orderByRaw(
                                                        "CASE 
                                                WHEN province = '" .
                                                            addslashes($receiverProvince) .
                                                            "' THEN 0 
                                                WHEN name LIKE '%" .
                                                            addslashes($receiverProvince) .
                                                            "%' THEN 1 
                                                WHEN name LIKE '%Sài Gòn%' THEN 2
                                                WHEN province LIKE '%" .
                                                            addslashes($receiverProvinceShort) .
                                                            "%' THEN 3
                                                ELSE 4 
                                            END",
                                                    )
                                                    ->first();
                                            }
                                        }
                                    @endphp

                                    @if ($toWarehouse)
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-arrow-right me-1"></i>{{ $toWarehouse->name }}
                                        </span><br>
                                        <small class="text-muted">{{ $toWarehouse->province ?? '' }}</small>
                                        @if ($hasBeenReceived)
                                            <br><small class="text-success"><i class="fas fa-check me-1"></i>Đã nhận</small>
                                        @else
                                            <br><small class="text-danger"><i class="fas fa-clock me-1"></i>Chưa
                                                nhận</small>
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
                                        $lastOutTransaction = \App\Models\WarehouseTransaction::where(
                                            'order_id',
                                            $order->id,
                                        )
                                            ->where('type', 'out')
                                            ->orderBy('transaction_date', 'desc')
                                            ->first();
                                    @endphp
                                    @if ($lastOutTransaction)
                                        <small>{{ $lastOutTransaction->transaction_date->format('d/m/Y H:i') }}</small>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        @php
                                            // Kiểm tra đơn hàng đã được kho đích nhận chưa
                                            $hasBeenReceivedByDestination = false;
                                            if ($order->to_warehouse_id) {
                                                $hasBeenReceivedByDestination = \App\Models\WarehouseTransaction::where(
                                                    'order_id',
                                                    $order->id,
                                                )
                                                    ->where('warehouse_id', $order->to_warehouse_id)
                                                    ->where('type', 'in')
                                                    ->where(function ($q) {
                                                        $q->where('notes', 'like', '%Nhận từ%')
                                                            ->orWhere('notes', 'like', '%từ kho%')
                                                            ->orWhere('notes', 'like', '%Nhận từ kho%');
                                                    })
                                                    ->exists();
                                            }

                                            // Chỉ cho phép phân công khi:
                                            // 1. Chưa có tài xế VÀ
                                            // 2. Kho đích chưa nhận được hàng (nếu có to_warehouse_id)
                                            $canAssignDriver =
                                                !$order->delivery_driver_id && !$hasBeenReceivedByDestination;
                                        @endphp

                                        @if ($canAssignDriver)
                                            {{-- <button class="btn btn-sm btn-success" onclick="assignDriver({{ $order->id }}, {{ $order->to_warehouse_id ?? 'null' }})" 
                                        title="{{ $order->to_warehouse_id ? 'Phân công tài xế vận chuyển tỉnh (kho đích chưa nhận được)' : 'Phân công tài xế giao hàng' }}">
                                    <i class="fas fa-user-plus"></i>
                                </button> --}}
                                        @else
                                            @if ($order->deliveryDriver)
                                                <span class="badge bg-success me-1"
                                                    title="Tài xế: {{ $order->deliveryDriver->name }} - {{ $hasBeenReceivedByDestination ? 'Kho đích đã nhận' : 'Đang vận chuyển' }}">
                                                    <i class="fas fa-user me-1"></i>{{ $order->deliveryDriver->name }}
                                                </span>
                                                @if ($hasBeenReceivedByDestination)
                                                    <br><small class="text-muted"><i
                                                            class="fas fa-check-circle me-1"></i>Kho đích đã nhận</small>
                                                @elseif($order->to_warehouse_id)
                                                    <br><small class="text-warning"><i class="fas fa-truck me-1"></i>Đang
                                                        vận chuyển</small>
                                                @endif
                                            @elseif($hasBeenReceivedByDestination)
                                                <span class="badge bg-info me-1">
                                                    <i class="fas fa-check-circle me-1"></i>Kho đích đã nhận
                                                </span>
                                            @endif
                                        @endif
                                        <a href="{{ route('admin.orders.show', $order->id) }}"
                                            class="btn btn-sm btn-primary" title="Xem chi tiết">
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

    {{-- 
<div class="card" id="deliverySection">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Đơn hàng đang giao</h5>
            <small>Danh sách đơn hàng đã được phân công tài xế giao hàng</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" action="{{ route('admin.delivery.index') }}" id="filterProvinceDeliveryForm" class="mb-0 d-inline-block">
                @if (request('province_in_transit'))
                <input type="hidden" name="province_in_transit" value="{{ request('province_in_transit') }}">
                @endif
                <select name="province_delivery" id="filterProvinceDelivery" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tất cả tỉnh/TP</option>
                    @foreach ($provinces ?? [] as $province)
                    <option value="{{ $province }}" {{ request('province_delivery') == $province ? 'selected' : '' }}>{{ $province }}</option>
                    @endforeach
                </select>
            </form>
            <button type="button" class="btn btn-sm btn-success" id="bulkAssignDeliveryDriverBtn" disabled onclick="bulkAssignDeliveryDriver()">
                <i class="fas fa-user-plus me-1"></i>Phân công tài xế nhiều đơn (<span id="selectedDeliveryCount">0</span>)
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="deliveryTable">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAllDelivery" title="Chọn tất cả">
                        </th>
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
                        <td>
                            @if (!$order->delivery_driver_id)
                            <input type="checkbox" class="order-checkbox-delivery" value="{{ $order->id }}" 
                                   data-tracking="{{ $order->tracking_number }}">
                            @endif
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
                            @if ($order->deliveryDriver)
                            <strong>{{ $order->deliveryDriver->name }}</strong><br>
                            <small class="text-muted">{{ $order->deliveryDriver->phone }}</small>
                            @else
                            <span class="text-muted">Chưa phân công</span>
                            @endif
                        </td>
                        <td>
                            @if ($order->delivery_scheduled_at)
                            <small>{{ $order->delivery_scheduled_at->format('d/m/Y H:i') }}</small>
                            @else
                            <span class="text-muted">Chưa có</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                @if (!$order->delivery_driver_id)
                                    <button type="button" class="btn btn-sm btn-success" onclick="assignDeliveryDriver({{ $order->id }}, {{ $order->to_warehouse_id ?? 'null' }})" title="Phân công tài xế">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                @elseif($order->status === 'out_for_delivery' && $order->delivery_driver_id)
                                    <button type="button" class="btn btn-sm btn-success" onclick="updateDeliveryStatus({{ $order->id }}, 'delivered', {{ $order->shipping_fee ?? 0 }}, {{ $order->return_fee ?? 0 }})" title="Giao hàng thành công">
                                        <i class="fas fa-check-circle"></i> Thành công
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="updateDeliveryStatus({{ $order->id }}, 'failed')" title="Giao hàng thất bại">
                                        <i class="fas fa-times-circle"></i> Thất bại
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
                        <td colspan="9" class="text-center text-muted">
                            <i class="fas fa-shipping-fast me-2"></i>Không có đơn hàng nào đang giao
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
--}}

    <!-- Đơn hàng đã nhận từ kho khác -->
    @if (isset($ordersReceivedFromWarehouses) && count($ordersReceivedFromWarehouses) > 0)
        <div class="card mb-4" id="receivedFromWarehousesSection">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>Đơn hàng đã nhận từ kho khác</h5>
                    <small>Danh sách đơn hàng đã được nhận vào kho này từ kho khác - Tổng:
                        {{ count($ordersReceivedFromWarehouses) }} đơn</small>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <form method="GET" action="{{ route('admin.delivery.index') }}" id="filterProvinceReceivedForm"
                        class="mb-0 d-inline-block">
                        @if (request('province_delivery'))
                            <input type="hidden" name="province_delivery" value="{{ request('province_delivery') }}">
                        @endif
                        @if (request('province_shipped_out'))
                            <input type="hidden" name="province_shipped_out"
                                value="{{ request('province_shipped_out') }}">
                        @endif
                        <select name="province_received" id="filterProvinceReceived" class="form-select form-select-sm"
                            onchange="this.form.submit()">
                            <option value="">Tất cả tỉnh/TP</option>
                            @foreach ($provinces ?? [] as $province)
                                <option value="{{ $province }}"
                                    {{ request('province_received') == $province ? 'selected' : '' }}>{{ $province }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                    <button type="button" class="btn btn-sm btn-success" id="bulkAssignDriverReceivedBtn" disabled
                        onclick="return bulkAssignDriver('received');">
                        <i class="fas fa-user-plus me-1"></i>Phân công tài xế nhiều đơn (<span
                            id="selectedReceivedCount">0</span>)
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="receivedFromWarehousesTable">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="selectAllReceived" title="Chọn tất cả">
                                </th>
                                <th>Mã vận đơn</th>
                                <th>Kho gửi</th>
                                <th>Kho nhận</th>
                                <th>Người nhận</th>
                                <th>Địa chỉ giao</th>
                                <th>Tỉnh/TP</th>
                                <th>Người gửi</th>
                                <th>COD</th>
                                <th>Phí trả hàng</th>
                                <th>Shipper trả hàng</th>
                                <th>Ngày nhận</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ordersReceivedFromWarehouses ?? [] as $order)
                                @php
                                    $isFailed = App\Models\OrderStatus::where('order_id', $order->id)
                                        ->where('status', 'failed')
                                        ->exists();
                                @endphp
                                @php
                                    $isReturnOrder = false;
                                    $returnNotes = '';
                                    if ($order->warehouseTransactions && $order->warehouseTransactions->isNotEmpty()) {
                                        $lastInTransaction = $order->warehouseTransactions->first();
                                        if (
                                            stripos($lastInTransaction->notes ?? '', 'Trả đơn hàng') !== false ||
                                            stripos($lastInTransaction->notes ?? '', 'Quay lại kho') !== false
                                        ) {
                                            $isReturnOrder = true;
                                            $returnNotes = $lastInTransaction->notes ?? '';
                                        }
                                    }
                                @endphp
                                <tr class="{{ $isReturnOrder ? 'table-warning' : '' }}">
                                    <td>
                                        <input type="checkbox" class="order-checkbox-received"
                                            value="{{ $order->id }}" data-tracking="{{ $order->tracking_number }}">
                                    </td>
                                    <td>
                                        @if (
                                            !str_contains($order->statuses->last()->notes, 'Trả đơn hàng thất bại về kho') ||
                                                ($order->status !== 'failed' && $order->previousWarehouse))
                                            <span
                                                class="badge bg-primary">{{ $order->previousWarehouse?->name ?? 'N/A' }}</span><br>
                                            <small
                                                class="text-muted">{{ $order->previousWarehouse->province ?? '' }}</small>
                                        @else
                                            <span class="text-muted">{{ $order->statuses->last()->notes ?? 'N/A' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $order->tracking_number }}</strong>
                                        @if ($isReturnOrder)
                                            <br><span class="badge bg-danger text-white" title="{{ $returnNotes }}">
                                                <i class="fas fa-undo me-1"></i>Đơn trả về
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($order->warehouse)
                                            <span class="badge bg-primary">{{ $order->warehouse->name }}</span><br>
                                            <small class="text-muted">{{ $order->warehouse->province ?? '' }}</small>
                                            @if ($isReturnOrder)
                                                <br><small class="text-danger"><i
                                                        class="fas fa-exclamation-triangle me-1"></i>Trả về kho</small>
                                            @endif
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
                                        <strong>{{ $order->sender_name }}</strong><br>
                                        <small class="text-muted">{{ $order->sender_phone }}</small>
                                        <br><a href="tel:{{ $order->sender_phone }}"
                                            class="btn btn-xs btn-outline-primary btn-sm mt-1" title="Gọi điện">
                                            <i class="fas fa-phone me-1"></i>Gọi
                                        </a>
                                    </td>
                                    <td>{{ number_format($order->cod_amount) }} đ</td>
                                    <td>
                                        @if ($order->return_fee && $order->return_fee > 0)
                                            <strong class="text-warning">{{ number_format($order->return_fee) }}
                                                đ</strong>
                                        @else
                                            <span class="text-muted">Chưa có</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($order->delivery_driver_id && $order->deliveryDriver)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Đã gán<br>
                                                <small>{{ $order->deliveryDriver->name }}</small><br>
                                                <small class="text-muted">{{ $order->deliveryDriver->phone }}</small>
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-times me-1"></i>Chưa gán
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($order->warehouseTransactions && $order->warehouseTransactions->isNotEmpty())
                                            @php
                                                $lastInTransaction = $order->warehouseTransactions->first();
                                            @endphp
                                            <small>{{ $lastInTransaction->transaction_date->format('d/m/Y H:i') }}</small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @php

                                                // dd($order->statuses->first()->notes, str_contains($order->statuses->last()->notes, 'Trả đơn hàng thất bại về kho'));
                                            @endphp
                                            @if (str_contains($order->statuses->last()->notes, 'Trả đơn hàng thất bại về kho') || $order->status === 'failed')
                                                <button type="button" class="btn btn-sm btn-success"
                                                    onclick="assignReturnShipper({{ $order->id }})"
                                                    title="Gán shipper trả hàng">
                                                    <i class="fas fa-user-plus"></i> Gán shipper
                                                </button>
                                            @elseif($order->status === 'out_for_delivery')
                                                <button type="button" class="btn btn-sm btn-success"
                                                    data-is-failed="{{ $isFailed ? 'true' : 'false' }}"
                                                    onclick="updateDeliveryStatus({{ $order->id }}, 'delivered', {{ $isFailed ? 30000 : $order->shipping_fee ?? 0 }}, {{ $order->return_fee ?? 0 }})"
                                                    title=" {{ $isFailed ? 'Giao hàng thành công' : 'Trả hàng thành công' }}">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                                @if (!$isFailed)
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="updateDeliveryStatus({{ $order->id }}, 'failed')"
                                                        title="Giao hàng thất bại">
                                                        <i class="fas fa-times-circle"></i>
                                                    </button>
                                                @endif
                                            @endif
                                            <a href="{{ route('admin.orders.show', $order->id) }}"
                                                class="btn btn-sm btn-primary" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Đơn hàng đã hủy/thất bại -->
    <div class="card" id="failedOrdersSection">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><i class="fas fa-times-circle me-2"></i>Đơn hàng đã hủy/thất bại</h5>
                <small>Danh sách đơn hàng đã hủy hoặc giao hàng thất bại - Cần trả về kho</small>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <form method="GET" action="{{ route('admin.delivery.index') }}" id="filterProvinceFailedForm"
                    class="mb-0 d-inline-block">
                    @if (request('province_delivery'))
                        <input type="hidden" name="province_delivery" value="{{ request('province_delivery') }}">
                    @endif
                    @if (request('province_in_transit'))
                        <input type="hidden" name="province_in_transit" value="{{ request('province_in_transit') }}">
                    @endif
                    <select name="province_failed" id="filterProvinceFailed" class="form-select form-select-sm"
                        onchange="this.form.submit()">
                        <option value="">Tất cả tỉnh/TP</option>
                        @foreach ($provinces ?? [] as $province)
                            <option value="{{ $province }}"
                                {{ request('province_failed') == $province ? 'selected' : '' }}>{{ $province }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="failedOrdersTable">
                    <thead>
                        <tr>
                            <th>Mã vận đơn</th>
                            <th>Người nhận</th>
                            <th>Địa chỉ giao</th>
                            <th>Tỉnh/TP</th>
                            <th>COD</th>
                            <th>Trạng thái</th>
                            <th>Lý do thất bại</th>
                            <th>Tài xế giao</th>
                            <th>Thời gian</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ordersFailed ?? [] as $order)
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
                                    @if ($order->status === 'failed')
                                        <span class="badge bg-danger">Thất bại</span>
                                    @elseif($order->status === 'cancelled')
                                        <span class="badge bg-warning text-dark">Đã hủy</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $order->status ?? 'N/A' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($order->failure_reason && trim($order->failure_reason) !== '')
                                        <small class="text-muted"
                                            title="{{ $order->failure_reason }}">{{ Str::limit($order->failure_reason, 50) }}</small>
                                    @else
                                        <span class="text-muted">Chưa có lý do</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($order->deliveryDriver)
                                        <strong>{{ $order->deliveryDriver->name }}</strong><br>
                                        <small class="text-muted">{{ $order->deliveryDriver->phone }}</small>
                                    @else
                                        <span class="text-muted">Chưa có</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($order->updated_at)
                                        <small>{{ $order->updated_at->format('d/m/Y H:i') }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-warning"
                                            onclick="returnToWarehouse({{ $order->id }})"
                                            title="Quay giao hàng để trả về kho">
                                            <i class="fas fa-undo me-1"></i> Quay giao hàng
                                        </button>
                                        <a href="{{ route('admin.orders.show', $order->id) }}"
                                            class="btn btn-sm btn-primary" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">
                                    <i class="fas fa-times-circle me-2"></i>Không có đơn hàng nào đã hủy/thất bại
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
                                @foreach ($drivers ?? [] as $driver)
                                    <option value="{{ $driver->id }}" data-driver-type="{{ $driver->driver_type }}">
                                        {{ $driver->name }} - {{ $driver->phone }}
                                        @if ($driver->driver_type === 'shipper')
                                            (Shipper)
                                        @elseif($driver->driver_type === 'intercity_driver')
                                            (Vận chuyển tỉnh)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted" id="driverSelectHint"></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Thời gian giao dự kiến</label>
                            <input type="datetime-local" name="delivery_scheduled_at" class="form-control"
                                value="{{ date('Y-m-d\TH:i') }}">
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

    <!-- Modal cập nhật trạng thái giao hàng -->
    <div class="modal fade" id="updateDeliveryStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="updateDeliveryStatusForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateDeliveryStatusModalTitle">Cập nhật trạng thái giao hàng</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="updateOrderId">
                        <input type="hidden" name="status" id="updateStatus">

                        <!-- Form cho giao hàng thành công -->
                        <div id="deliveredForm" style="display: none;">
                            <div class="mb-3" id="shippingFeeField">
                                <label class="form-label">Phí vận chuyển đã thu (đ) <span
                                        class="text-danger">*</span></label>
                                <input type="number" name="shipping_fee" class="form-control" id="shippingFeeInput"
                                    min="0" step="0.01" value="30000">
                                <small class="text-muted">Nhập số tiền phí vận chuyển đã thu từ khách hàng (mặc định:
                                    30,000 đ)</small>
                            </div>
                            <div class="mb-3" id="returnFeeField" style="display: none;">
                                <label class="form-label">Phí trả hàng đã thu (đ) <span
                                        class="text-danger">*</span></label>
                                <input type="number" name="return_fee_collected" class="form-control"
                                    id="returnFeeInput" min="0" step="0.01">
                                <small class="text-muted">Nhập số tiền phí trả hàng đã thu từ người nhận (receiver)</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ghi chú giao hàng</label>
                                <textarea name="delivery_notes" class="form-control" rows="3"
                                    placeholder="Ghi chú về việc giao hàng (nếu có)"></textarea>
                            </div>
                        </div>

                        <!-- Form cho giao hàng thất bại -->
                        <div id="failedForm" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Lý do giao hàng thất bại <span
                                        class="text-danger">*</span></label>
                                <textarea name="failure_reason" class="form-control" rows="3" id="failureReasonInput"
                                    placeholder="Ví dụ: Khách hàng không nhận, địa chỉ sai, không liên lạc được..."></textarea>
                                <small class="text-danger" id="failureReasonError" style="display: none;">Vui lòng nhập
                                    lý do giao hàng thất bại</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ghi chú thêm</label>
                                <textarea name="delivery_notes" class="form-control" rows="2" placeholder="Ghi chú thêm (nếu có)"></textarea>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="return_to_warehouse"
                                        id="returnToWarehouse" value="1" checked>
                                    <label class="form-check-label" for="returnToWarehouse">
                                        Trả đơn hàng về kho để phân công lại
                                    </label>
                                </div>
                                <small class="text-muted">Nếu chọn, đơn hàng sẽ được trả về kho và có thể phân công lại
                                    shipper khác</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary" id="confirmUpdateStatusBtn">Xác nhận</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Return Shipper Modal -->
    <div class="modal fade" id="assignReturnShipperModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="assignReturnShipperForm" method="POST">
                    @csrf
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">
                            <i class="fas fa-undo me-2"></i>Gán shipper trả hàng
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Lưu ý:</strong> Đây là đơn hàng trả về. Shipper sẽ trả hàng cho người gửi hoặc đưa về
                            kho.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Chọn shipper trả hàng <span class="text-danger">*</span></label>
                            <select name="driver_id" class="form-select" required id="returnShipperSelect">
                                <option value="">-- Chọn shipper --</option>
                            </select>
                            <small class="text-muted">Chọn shipper để trả hàng cho người gửi</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phí trả hàng (đ)</label>
                            <input type="number" name="return_fee" class="form-control" min="0" step="0.01"
                                value="30000" placeholder="Nhập phí trả hàng">
                            <small class="text-muted">Phí trả hàng mặc định: 30,000 đ. Sẽ được thu từ người gửi khi shipper
                                trả hàng</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Ghi chú thêm (nếu có)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-user-plus me-2"></i>Gán shipper trả hàng
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Assign Driver Modal -->
    <div class="modal fade" id="bulkAssignDriverModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="bulkAssignDriverForm" method="POST"
                    action="{{ route('admin.delivery.bulk-assign-driver') }}">
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
                                @foreach ($drivers ?? [] as $driver)
                                    <option value="{{ $driver->id }}" data-driver-type="{{ $driver->driver_type }}">
                                        {{ $driver->name }} - {{ $driver->phone }}
                                        @if ($driver->driver_type === 'shipper')
                                            (Shipper)
                                        @elseif($driver->driver_type === 'intercity_driver')
                                            (Vận chuyển tỉnh)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted" id="bulkDriverSelectHint">Chọn tài xế phù hợp với loại đơn hàng đã
                                chọn</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Thời gian giao dự kiến</label>
                            <input type="datetime-local" name="delivery_scheduled_at" class="form-control"
                                value="{{ date('Y-m-d\TH:i') }}">
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
            @if (isset($ordersShippedOut) && count($ordersShippedOut) > 0)
                var shippedOutTable = $('#shippedOutTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
                    },
                    responsive: true,
                    pageLength: 25,
                    order: [
                        [8, 'desc']
                    ],
                    drawCallback: function() {
                        $('.order-checkbox-shipped-out').off('change').on('change', function() {
                            updateSelectedShippedOutCount();
                            const total = $('.order-checkbox-shipped-out').length;
                            const checked = $('.order-checkbox-shipped-out:checked').length;
                            $('#selectAllShippedOut').prop('checked', total === checked &&
                                total > 0);
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
            @if (isset($ordersIncoming) && count($ordersIncoming) > 0)
                var incomingTable = $('#incomingTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
                    },
                    responsive: true,
                    pageLength: 25,
                    order: [
                        [8, 'desc']
                    ],
                    drawCallback: function() {
                        $('.order-checkbox-incoming').off('change').on('change', function() {
                            updateSelectedIncomingCount();
                            const total = $('.order-checkbox-incoming').length;
                            const checked = $('.order-checkbox-incoming:checked').length;
                            $('#selectAllIncoming').prop('checked', total === checked && total >
                                0);
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

            // Reset modal khi đóng
            $('#assignDriverModal').on('hidden.bs.modal', function() {
                // Reset tất cả option về trạng thái ban đầu
                $('#driverSelect option').show();
                $('#driverSelect').val('');
                $('#orderToWarehouseWarning').addClass('d-none');
                $('#driverSelectHint').text('');
            });

            $('#bulkAssignDriverModal').on('hidden.bs.modal', function() {
                // Reset tất cả option về trạng thái ban đầu
                $('#bulkDriverSelect option').show();
                $('#bulkDriverSelect').val('');
                $('#bulkDriverSelectHint').text('Chọn tài xế phù hợp với loại đơn hàng đã chọn');
            });

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

            // Xử lý chọn nhiều đơn hàng trong phần "Đơn hàng đang giao"
            function updateSelectedDeliveryCount() {
                const selected = $('.order-checkbox-delivery:checked').map(function() {
                    return $(this).val();
                }).get();

                const count = selected.length;
                $('#selectedDeliveryCount').text(count);
                $('#bulkAssignDeliveryDriverBtn').prop('disabled', count === 0);
            }

            // Xử lý chọn tất cả đơn hàng trong phần "Đơn hàng đang giao"
            $('#selectAllDelivery').on('change', function() {
                $('.order-checkbox-delivery').prop('checked', this.checked);
                updateSelectedDeliveryCount();
            });

            $(document).on('change', '.order-checkbox-delivery', function() {
                updateSelectedDeliveryCount();
                const total = $('.order-checkbox-delivery').length;
                const checked = $('.order-checkbox-delivery:checked').length;
                $('#selectAllDelivery').prop('checked', total === checked && total > 0);
            });

            // Trigger update on page load
            updateSelectedDeliveryCount();

            // DataTable cho bảng đơn hàng đang giao
            @if (isset($ordersReadyForDelivery) && count($ordersReadyForDelivery) > 0)
                var deliveryTable = $('#deliveryTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
                    },
                    responsive: true,
                    pageLength: 25,
                    order: [
                        [6, 'desc']
                    ]
                });
            @endif

            // DataTable cho bảng đơn hàng thất bại
            @if (isset($ordersFailed) && count($ordersFailed) > 0)
                var failedOrdersTable = $('#failedOrdersTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
                    },
                    responsive: true,
                    pageLength: 25,
                    order: [
                        [8, 'desc']
                    ]
                });
            @endif

            // DataTable cho bảng đơn hàng đã nhận từ kho khác
            @if (isset($ordersReceivedFromWarehouses) && count($ordersReceivedFromWarehouses) > 0)
                var receivedFromWarehousesTable = $('#receivedFromWarehousesTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
                    },
                    responsive: true,
                    pageLength: 25,
                    order: [
                        [8, 'desc']
                    ],
                    drawCallback: function() {
                        $('.order-checkbox-received').off('change').on('change', function() {
                            updateSelectedReceivedCount();
                            const total = $('.order-checkbox-received').length;
                            const checked = $('.order-checkbox-received:checked').length;
                            $('#selectAllReceived').prop('checked', total === checked && total >
                                0);
                        });
                    }
                });

                $(document).on('change', '#selectAllReceived', function() {
                    const isChecked = this.checked;
                    $('.order-checkbox-received').prop('checked', isChecked);
                    updateSelectedReceivedCount();
                });
            @endif
        });

        function assignDriver(orderId, toWarehouseId) {
            console.log('assignDriver called', {
                orderId,
                toWarehouseId
            });

            $('#orderId').val(orderId);
            const assignUrl = '{{ route('admin.delivery.assign-driver', ':id') }}'.replace(':id', orderId);
            $('#assignDriverForm').attr('action', assignUrl);

            const toWarehouseIdNum = parseInt(toWarehouseId);
            const hasToWarehouse = toWarehouseId && toWarehouseId !== null && toWarehouseId !== 'null' && toWarehouseId !==
                '' && !isNaN(toWarehouseIdNum) && toWarehouseIdNum > 0;

            const driverType = hasToWarehouse ? 'intercity_driver' : 'shipper';

            loadShippersToSelect('#driverSelect', driverType, function() {
                if (hasToWarehouse) {
                    $('#orderToWarehouseWarning').removeClass('d-none');
                    $('#driverSelectHint').html(
                        '<strong>Lưu ý:</strong> Đơn hàng đã xuất từ kho cần chuyển đến kho khác. Chỉ chọn <strong>tài xế vận chuyển tỉnh</strong>.'
                    );
                    $('#driverSelect option').each(function() {
                        const $option = $(this);
                        const dt = $option.data('driver-type');
                        const val = $option.val();
                        if (!val || val === '') {
                            $option.show();
                            return;
                        }
                        if (dt === 'intercity_driver') {
                            $option.show();
                        } else {
                            $option.hide();
                        }
                    });
                } else {
                    $('#orderToWarehouseWarning').addClass('d-none');
                    $('#driverSelectHint').text('Chọn tài xế shipper để giao hàng cho khách hàng');
                    $('#driverSelect option').each(function() {
                        const $option = $(this);
                        const dt = $option.data('driver-type');
                        const val = $option.val();
                        if (!val || val === '') {
                            $option.show();
                            return;
                        }
                        if (dt === 'shipper') {
                            $option.show();
                        } else {
                            $option.hide();
                        }
                    });
                }

                const modal = $('#assignDriverModal');
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const bsModal = new bootstrap.Modal(modal[0]);
                    bsModal.show();
                } else {
                    modal.modal('show');
                }
            });
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
                            showNotification('success', response.message ||
                                'Đã phân công tài xế thành công!');
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
                            errorMessage =
                                'Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng.';
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
                            showNotification('success', response.message ||
                                `Đã phân công tài xế cho ${orderIds.length} đơn hàng thành công!`
                            );
                        } else {
                            alert(response.message ||
                                `Đã phân công tài xế cho ${orderIds.length} đơn hàng thành công!`
                            );
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
                            errorMessage =
                                'Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng.';
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
                url: '{{ route('admin.warehouses.receive-order') }}',
                method: 'POST',
                data: {
                    order_id: orderId,
                    from_warehouse_id: fromWarehouseId || null,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    // Hiển thị thông báo thành công
                    if (typeof showNotification === 'function') {
                        showNotification('success', response.message ||
                            'Đơn hàng đã được nhận vào kho thành công!');
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
                url: '{{ route('admin.warehouses.bulk-receive-order') }}',
                method: 'POST',
                data: {
                    order_ids: selected,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    // Hiển thị thông báo thành công
                    if (typeof showNotification === 'function') {
                        showNotification('success', response.message ||
                            `Đã nhận ${selected.length} đơn hàng vào kho thành công!`);
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

            // Reset tất cả option về trạng thái ban đầu (hiển thị tất cả)
            $('#bulkDriverSelect option').show();

            $('#bulkDriverSelect').val('');

            const driverType = section === 'shippedOut' ? 'intercity_driver' : 'shipper';

            loadShippersToSelect('#bulkDriverSelect', driverType, function() {
                if (section === 'shippedOut') {
                    $('#bulkDriverSelectHint').html(
                        '<strong>Lưu ý:</strong> Đơn hàng đã xuất từ kho cần chuyển đến kho khác. Chỉ chọn <strong>tài xế vận chuyển tỉnh</strong>.'
                    );
                    $('#bulkDriverSelect option').each(function() {
                        const $option = $(this);
                        const dt = $option.data('driver-type');
                        const val = $option.val();
                        if (!val || val === '') {
                            $option.show();
                            return;
                        }
                        if (dt === 'intercity_driver') {
                            $option.show();
                        } else {
                            $option.hide();
                        }
                    });
                } else {
                    $('#bulkDriverSelectHint').text('Chọn tài xế shipper để giao hàng cho khách hàng');
                    $('#bulkDriverSelect option').each(function() {
                        const $option = $(this);
                        const dt = $option.data('driver-type');
                        const val = $option.val();
                        if (!val || val === '') {
                            $option.show();
                            return;
                        }
                        if (dt === 'shipper') {
                            $option.show();
                        } else {
                            $option.hide();
                        }
                    });
                }

                let infoText = `Đã chọn ${selected.length} đơn hàng`;
                if (provinces.length > 0) {
                    infoText += `\nCác tỉnh nhận: ${provinces.join(', ')}`;
                }
                $('#bulkAssignInfo').html(infoText.replace(/\n/g, '<br>'));

                $('#bulkAssignDriverModal').modal('show');
                $('#bulkSelectedCount').text(selected.length);
            });

            return false;
        }

        function loadShippersToSelect(selectId, driverType, callback) {
            const $select = $(selectId);
            $select.html('<option value="">-- Đang tải... --</option>');

            $.ajax({
                url: '{{ route('admin.api.shippers') }}',
                method: 'GET',
                data: {
                    driver_type: driverType
                },
                success: function(response) {
                    if (response.success && response.drivers) {
                        $select.html('<option value="">-- Chọn tài xế --</option>');
                        response.drivers.forEach(function(driver) {
                            const label = driver.name + ' - ' + driver.phone +
                                (driver.driver_type === 'shipper' ? ' (Shipper)' :
                                    ' (Vận chuyển tỉnh)');
                            $select.append($('<option></option>')
                                .attr('value', driver.id)
                                .attr('data-driver-type', driver.driver_type)
                                .text(label));
                        });
                        if (callback) callback();
                    } else {
                        $select.html('<option value="">-- Không có tài xế --</option>');
                    }
                },
                error: function() {
                    $select.html('<option value="">-- Lỗi tải dữ liệu --</option>');
                }
            });
        }

        function assignReturnShipper(orderId) {
            $('#assignReturnShipperForm').attr('action', '{{ route('admin.delivery.assign-return-shipper', ':id') }}'
                .replace(':id', orderId));

            loadShippersToSelect('#returnShipperSelect', 'shipper', function() {
                $('#returnShipperSelect option').each(function() {
                    const $option = $(this);
                    const dt = $option.data('driver-type');
                    const val = $option.val();
                    if (!val || val === '') {
                        $option.show();
                        return;
                    }
                    if (dt === 'shipper') {
                        $option.show();
                    } else {
                        $option.hide();
                    }
                });

                const modal = $('#assignReturnShipperModal');
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const bsModal = new bootstrap.Modal(modal[0]);
                    bsModal.show();
                } else {
                    modal.modal('show');
                }
            });
        }

        function assignDeliveryDriver(orderId, toWarehouseId) {
            $('#orderId').val(orderId);
            $('#assignDriverForm').attr('action', '{{ route('admin.delivery.assign-driver', ':id') }}'.replace(':id',
                orderId));

            const toWarehouseIdNum = parseInt(toWarehouseId);
            const hasToWarehouse = toWarehouseId && toWarehouseId !== null && toWarehouseId !== 'null' && toWarehouseId !==
                '' && !isNaN(toWarehouseIdNum) && toWarehouseIdNum > 0;

            const driverType = hasToWarehouse ? 'intercity_driver' : 'shipper';

            loadShippersToSelect('#driverSelect', driverType, function() {
                if (hasToWarehouse) {
                    $('#orderToWarehouseWarning').removeClass('d-none');
                    $('#driverSelectHint').html(
                        '<strong>Lưu ý:</strong> Đơn hàng đã xuất từ kho cần chuyển đến kho khác. Chỉ chọn <strong>tài xế vận chuyển tỉnh</strong>.'
                    );
                    $('#driverSelect option').each(function() {
                        const $option = $(this);
                        const dt = $option.data('driver-type');
                        const val = $option.val();
                        if (!val || val === '') {
                            $option.show();
                            return;
                        }
                        if (dt === 'intercity_driver') {
                            $option.show();
                        } else {
                            $option.hide();
                        }
                    });
                } else {
                    $('#orderToWarehouseWarning').addClass('d-none');
                    $('#driverSelectHint').text('Chọn tài xế shipper để giao hàng cho khách hàng');
                    $('#driverSelect option').each(function() {
                        const $option = $(this);
                        const dt = $option.data('driver-type');
                        const val = $option.val();
                        if (!val || val === '') {
                            $option.show();
                            return;
                        }
                        if (dt === 'shipper') {
                            $option.show();
                        } else {
                            $option.hide();
                        }
                    });
                }

                const modal = $('#assignDriverModal');
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const bsModal = new bootstrap.Modal(modal[0]);
                    bsModal.show();
                } else {
                    modal.modal('show');
                }
            });
        }

        // Phân công tài xế giao hàng nhiều đơn trong phần "Đơn hàng đang giao"
        function bulkAssignDeliveryDriver() {
            const selected = $('.order-checkbox-delivery:checked').map(function() {
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
            $('#bulkAssignInfo').html(
                `Bạn đang phân công tài xế cho <strong>${selected.length}</strong> đơn hàng trong kho.`);

            // Set action cho form
            $('#bulkAssignDriverForm').attr('action', '{{ route('admin.delivery.bulk-assign-driver') }}');

            // Sử dụng Bootstrap 5 modal API
            const modal = $('#bulkAssignDriverModal');
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const bsModal = new bootstrap.Modal(modal[0]);
                bsModal.show();
            } else {
                modal.modal('show');
            }
            return false;
        }

        // Cập nhật trạng thái giao hàng (thành công hoặc thất bại)
        function updateDeliveryStatus(orderId, status, shippingFee = 0, returnFee = 0) {
            // Set order ID và status
            $('#updateOrderId').val(orderId);
            $('#updateStatus').val(status);

            // Set form action
            $('#updateDeliveryStatusForm').attr('action', '{{ route('admin.delivery.update-status', ':id') }}'.replace(
                ':id', orderId));

            // Reset form
            $('#updateDeliveryStatusForm')[0].reset();
            $('#updateOrderId').val(orderId);
            $('#updateStatus').val(status);
            let isFailed = $('#updateDeliveryStatusForm').data('is-failed');
            // Xóa tất cả required và ẩn tất cả form trước
            $('#shippingFeeInput').prop('required', false);
            $('#returnFeeInput').prop('required', false);
            $('#deliveredForm').hide();
            $('#failedForm').hide();

            if (status === 'delivered') {
                // Kiểm tra xem có phải đơn trả về không
                const isReturnOrder = returnFee && returnFee > 0;

                if (isReturnOrder) {
                    // Đơn trả về - hiển thị phí trả hàng

                    $('#updateDeliveryStatusModalTitle').text('Trả hàng thành công');
                    $('#shippingFeeField').hide();
                    $('#returnFeeField').show();
                    $('#returnFeeInput').prop('required', true);
                    // Phí trả hàng mặc định là 30,000 đ
                    $('#returnFeeInput').val(isFailed ? returnFee : shippingFee);
                    $('#returnFeeInput').removeClass('is-invalid');
                    $('#returnFeeInput').focus();
                } else {
                    // Đơn hàng thường - hiển thị phí vận chuyển
                    $('#updateDeliveryStatusModalTitle').text('Giao hàng thành công');
                    $('#shippingFeeField').show();
                    $('#returnFeeField').hide();
                    $('#shippingFeeInput').prop('required', true);
                    // Phí vận chuyển: lấy từ shippingFee được truyền vào, nếu không có thì mặc định 30,000 đ
                    $('#shippingFeeInput').val(shippingFee && shippingFee > 0 ? shippingFee : 30000);
                    $('#shippingFeeInput').removeClass('is-invalid');
                    $('#shippingFeeInput').focus();
                }

                $('#deliveredForm').show();
                $('#failedForm').hide();
            } else if (status === 'failed') {
                // Giao hàng thất bại
                $('#updateDeliveryStatusModalTitle').text('Giao hàng thất bại');
                $('#deliveredForm').hide();
                $('#failedForm').show();

                // Xóa required khỏi shipping_fee khi form bị ẩn
                $('#shippingFeeInput').prop('required', false);
                $('#returnFeeInput').prop('required', false);

                $('#returnToWarehouse').prop('checked', true);
                $('#failureReasonInput').val('').removeClass('is-invalid');
                $('#failureReasonError').hide();
                $('#failureReasonInput').focus();
            }

            // Mở modal
            const modal = $('#updateDeliveryStatusModal');
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const bsModal = new bootstrap.Modal(modal[0]);
                bsModal.show();
            } else {
                modal.modal('show');
            }
        }

        // Xử lý submit form cập nhật trạng thái giao hàng
        $(document).on('submit', '#updateDeliveryStatusForm', function(e) {
            e.preventDefault();

            const orderId = $('#updateOrderId').val();
            const status = $('#updateStatus').val();

            // Validation
            if (status === 'delivered') {
                const shippingFee = $('#shippingFeeInput').val();
                if (!shippingFee || shippingFee.trim() === '' || parseFloat(shippingFee) < 0) {
                    $('#shippingFeeInput').addClass('is-invalid').focus();
                    alert('Vui lòng nhập phí vận chuyển đã thu (phải lớn hơn hoặc bằng 0).');
                    return false;
                }
                $('#shippingFeeInput').removeClass('is-invalid');
            } else if (status === 'failed') {
                const failureReason = $('#failureReasonInput').val();
                if (!failureReason || failureReason.trim() === '') {
                    $('#failureReasonError').show();
                    $('#failureReasonInput').addClass('is-invalid').focus();
                    setTimeout(function() {
                        $('#failureReasonError').hide();
                        $('#failureReasonInput').removeClass('is-invalid');
                    }, 3000);
                    return false;
                }
                $('#failureReasonError').hide();
                $('#failureReasonInput').removeClass('is-invalid');
            }

            const data = $(this).serializeArray();
            data.push({
                name: 'status',
                value: status
            });

            const submitData = {};
            data.forEach(function(item) {
                if (status === 'delivered' && item.name === 'failure_reason') {
                    return;
                }
                if (status === 'failed' && item.name === 'shipping_fee') {
                    return;
                }
                submitData[item.name] = item.value;
            });

            if (status === 'failed') {
                submitData['return_to_warehouse'] = $('#returnToWarehouse').is(':checked') ? 1 : 0;
            }

            if (status === 'delivered') {
                submitData['cod_collected'] = 0;
                if (submitData['shipping_fee']) {
                    submitData['shipping_fee'] = parseFloat(submitData['shipping_fee']) || 0;
                }
            }

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: submitData,
                success: function(response) {
                    // Đóng modal
                    const modal = $('#updateDeliveryStatusModal');
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const bsModal = bootstrap.Modal.getInstance(modal[0]);
                        if (bsModal) bsModal.hide();
                    } else {
                        modal.modal('hide');
                    }

                    if (typeof showNotification === 'function') {
                        showNotification('success', response.message ||
                            'Đã cập nhật trạng thái giao hàng thành công!');
                    } else {
                        alert(response.message || 'Đã cập nhật trạng thái giao hàng thành công!');
                    }
                    location.reload();
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
        });

        // Quay giao hàng để trả về kho
        function returnToWarehouse(orderId) {
            if (!confirm('Bạn có chắc chắn muốn quay giao hàng để trả đơn hàng này về kho gửi ban đầu?')) {
                return;
            }

            $.ajax({
                url: '{{ route('admin.delivery.return-to-warehouse', ':id') }}'.replace(':id', orderId),
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        if (typeof showNotification === 'function') {
                            showNotification('success', response.message ||
                                'Đã trả đơn hàng về kho gửi ban đầu thành công.');
                        } else {
                            alert(response.message || 'Đã trả đơn hàng về kho gửi ban đầu thành công.');
                        }
                        // Reload trang sau 1 giây
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        if (typeof showNotification === 'function') {
                            showNotification('error', response.message || 'Có lỗi xảy ra.');
                        } else {
                            alert(response.message || 'Có lỗi xảy ra.');
                        }
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Có lỗi xảy ra khi trả đơn hàng về kho.';
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
