@extends('customer.layout')

@section('title', 'Tạo đơn hàng mới')
@section('page-title', 'Tạo đơn hàng mới')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Tạo đơn hàng mới</h5>
    </div>
    <div class="card-body">
                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    <form action="{{ route('customer.orders.store') }}" method="POST" id="orderForm">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2 mb-3">Thông tin người nhận</h6>
                                <div class="mb-3">
                                    <label class="form-label">Tên người nhận <span class="text-danger">*</span></label>
                                    <input type="text" name="receiver_name" class="form-control" required value="{{ old('receiver_name') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="text" name="receiver_phone" class="form-control" required value="{{ old('receiver_phone') }}">
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Tỉnh/Thành <span class="text-danger">*</span></label>
                                        <select name="receiver_province" id="receiver_province" class="form-select" required>
                                            <option value="">-- Chọn Tỉnh/Thành --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phường/Xã <span class="text-danger">*</span></label>
                                        <select name="receiver_ward" id="receiver_ward" class="form-select" disabled required>
                                            <option value="">-- Chọn Tỉnh/Thành trước --</option>
                                        </select>
                                    </div>
                                </div>
                                <input type="hidden" name="receiver_district" id="receiver_district" value="">
                                <div class="mb-3">
                                    <label class="form-label">Địa chỉ chi tiết (Số nhà, tên đường) <span class="text-danger">*</span></label>
                                    <textarea name="receiver_address" id="receiver_address" class="form-control" rows="2" required placeholder="Nhập địa chỉ chi tiết sau khi đã chọn Tỉnh/Thành và Phường/Xã" disabled>{{ old('receiver_address') }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Kho vận chuyển đến <span class="text-danger">*</span></label>
                                    <select name="to_warehouse_id" id="to_warehouse_id" class="form-select" required>
                                        <option value="">-- Chọn Tỉnh/Thành trước --</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2 mb-3">Thông tin hàng hóa</h6>
                                <div class="mb-3">
                                    <label class="form-label">Loại hàng</label>
                                    <input type="text" name="item_type" class="form-control" value="{{ old('item_type') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Trọng lượng (kg) <span class="text-danger">*</span></label>
                                    <input type="number" name="weight" class="form-control" step="0.01" min="0" required value="{{ old('weight') }}" id="weight">
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Chiều dài (cm)</label>
                                        <input type="number" name="length" class="form-control" step="0.01" min="0" value="{{ old('length') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Chiều rộng (cm)</label>
                                        <input type="number" name="width" class="form-control" step="0.01" min="0" value="{{ old('width') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Chiều cao (cm)</label>
                                        <input type="number" name="height" class="form-control" step="0.01" min="0" value="{{ old('height') }}">
                                    </div>
                                </div>
                                <div class="mb-3 mt-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_fragile" value="1" id="is_fragile">
                                        <label class="form-check-label" for="is_fragile">
                                            Hàng dễ vỡ
                                        </label>
                                    </div>
                                </div>
                                
                                <h6 class="border-bottom pb-2 mb-3 mt-4">Thông tin dịch vụ</h6>
                                <div class="mb-3">
                                    <label class="form-label">Loại dịch vụ</label>
                                    <select name="service_type" class="form-select" id="service_type">
                                        <option value="standard" {{ old('service_type') == 'standard' ? 'selected' : '' }}>Tiêu chuẩn</option>
                                        <option value="express" {{ old('service_type') == 'express' ? 'selected' : '' }}>Hỏa tốc</option>
                                        <option value="economy" {{ old('service_type') == 'economy' ? 'selected' : '' }}>Tiết kiệm</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tiền thu hộ COD (đ)</label>
                                    <input type="number" name="cod_amount" class="form-control" step="0.01" min="0" value="{{ old('cod_amount', 0) }}" id="cod_amount">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Ghi chú</label>
                                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phương thức nhận hàng <span class="text-danger">*</span></label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pickup_method" id="pickup_method_driver" value="driver" checked>
                                        <label class="form-check-label" for="pickup_method_driver">
                                            <i class="fas fa-truck me-1"></i>Tài xế đến lấy hàng (+20,000 đ)
                                        </label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="radio" name="pickup_method" id="pickup_method_warehouse" value="warehouse">
                                        <label class="form-check-label" for="pickup_method_warehouse">
                                            <i class="fas fa-warehouse me-1"></i>Đưa đến kho (miễn phí)
                                        </label>
                                    </div>
                                </div>
                                <div class="alert alert-info">
                                    <strong>Phí vận chuyển ước tính:</strong>
                                    <div id="estimated_fee" class="h4 mb-0">0 đ</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('customer.orders.index') }}" class="btn btn-secondary">Hủy</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Tạo đơn hàng
                            </button>
                        </div>
                    </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Load Vietnam addresses data
    let vietnamAddresses = null;
    
    $.getJSON('/data/vietnam-addresses-full.json', function(data) {
        vietnamAddresses = data;
        initializeAddressSelects();
    }).fail(function() {
        console.error('Không thể tải dữ liệu địa chỉ');
    });
    
    // Initialize address selects
    function initializeAddressSelects() {
        if (!vietnamAddresses) return;
        
        // Populate receiver province select
        const receiverProvinceSelect = $('#receiver_province');
        receiverProvinceSelect.find('option:not(:first)').remove();
        vietnamAddresses.provinces.forEach(province => {
            receiverProvinceSelect.append(`<option value="${province.name}">${province.name}</option>`);
        });
    }
    
    // Load wards for a province (load all wards from all districts)
    function loadWardsForProvince(provinceName) {
        if (!vietnamAddresses) return;
        
        const $wardSelect = $('#receiver_ward');
        $wardSelect.prop('disabled', true).html('<option value="">Đang tải...</option>');
        
        const province = vietnamAddresses.provinces.find(p => p.name === provinceName);
        
        if (province && province.districts) {
            const allWards = [];
            province.districts.forEach(district => {
                if (district.wards) {
                    district.wards.forEach(ward => {
                        allWards.push(ward.name);
                    });
                }
            });
            
            // Sort and populate
            allWards.sort();
            $wardSelect.html('<option value="">-- Chọn Phường/Xã --</option>');
            allWards.forEach(ward => {
                $wardSelect.append(`<option value="${ward}">${ward}</option>`);
            });
            $wardSelect.prop('disabled', false);
        } else {
            $wardSelect.html('<option value="">Không có dữ liệu</option>');
            $wardSelect.prop('disabled', true);
        }
    }
    
    // Load warehouses for a province
    function loadWarehousesForProvince(provinceName) {
        const $warehouseSelect = $('#to_warehouse_id');
        
        if (!provinceName) {
            $warehouseSelect.prop('disabled', true).html('<option value="">-- Chọn Tỉnh/Thành trước --</option>');
            return;
        }
        
        // Show loading
        $warehouseSelect.prop('disabled', true).html('<option value="">Đang tải kho...</option>');
        
        $.ajax({
            url: '/customer/api/warehouses',
            method: 'GET',
            data: { province: provinceName },
            success: function(response) {
                let warehouses = Array.isArray(response) ? response : [];
                $warehouseSelect.html('<option value="">-- Chọn kho vận chuyển đến --</option>');
                
                if (warehouses.length === 0) {
                    $warehouseSelect.append('<option value="">Không có kho nào ở tỉnh này</option>');
                    $warehouseSelect.prop('disabled', true);
                } else {
                    warehouses.forEach(function(warehouse) {
                        $warehouseSelect.append(`<option value="${warehouse.id}">${warehouse.code} - ${warehouse.name} (${warehouse.address})</option>`);
                    });
                    $warehouseSelect.prop('disabled', false);
                }
            },
            error: function(xhr) {
                console.error('Lỗi khi tải danh sách kho:', xhr);
                $warehouseSelect.html('<option value="">Lỗi khi tải danh sách kho</option>');
                $warehouseSelect.prop('disabled', true);
            }
        });
    }
    
    // Handle receiver province change
    $(document).on('change', '#receiver_province', function() {
        const provinceName = $(this).val();
        const $wardSelect = $('#receiver_ward');
        const $addressInput = $('#receiver_address');
        const $warehouseSelect = $('#to_warehouse_id');
        
        // Reset and disable ward, address, and warehouse
        $wardSelect.prop('disabled', true).html('<option value="">-- Chọn Tỉnh/Thành trước --</option>');
        $addressInput.prop('disabled', true).val('').attr('placeholder', 'Nhập địa chỉ chi tiết sau khi đã chọn Tỉnh/Thành và Phường/Xã');
        $warehouseSelect.prop('disabled', true).html('<option value="">-- Chọn Tỉnh/Thành trước --</option>');
        
        if (!provinceName || !vietnamAddresses) {
            calculateShippingFee();
            return;
        }
        
        // Load wards for this province
        loadWardsForProvince(provinceName);
        
        // Load warehouses for this province
        loadWarehousesForProvince(provinceName);
        
        // Trigger shipping fee calculation
        setTimeout(function() {
            calculateShippingFee();
        }, 100);
    });
    
    // Handle receiver ward change - enable address input
    $(document).on('change', '#receiver_ward', function() {
        const ward = $(this).val();
        const $addressInput = $('#receiver_address');
        
        if (ward) {
            $addressInput.prop('disabled', false).attr('placeholder', 'Nhập địa chỉ chi tiết (Số nhà, tên đường)');
        } else {
            $addressInput.prop('disabled', true).val('').attr('placeholder', 'Nhập địa chỉ chi tiết sau khi đã chọn Tỉnh/Thành và Phường/Xã');
        }
        
        calculateShippingFee();
    });
    
    // Calculate shipping fee
    function calculateShippingFee() {
        const weight = parseFloat($('#weight').val()) || 0;
        const receiverProvince = $('#receiver_province').val();
        const serviceType = $('#service_type').val() || 'standard';
        const codAmount = parseFloat($('#cod_amount').val()) || 0;
        
        if (!receiverProvince || weight <= 0) {
            $('#estimated_fee').text('0 đ');
            return;
        }
        
        $.ajax({
            url: '/customer/api/shipping-fees/calculate',
            method: 'POST',
            data: {
                from_province: '{{ $customer->province ?? $warehouse->province ?? "Nghệ An" }}',
                to_province: receiverProvince,
                weight: weight,
                service_type: serviceType,
                cod_amount: codAmount
            },
            success: function(response) {
                const shippingFee = response.shipping_fee || 0;
                const pickupMethod = $('input[name="pickup_method"]:checked').val();
                const pickupFee = (pickupMethod === 'driver') ? 20000 : 0;
                const totalFee = shippingFee + pickupFee;
                
                $('#estimated_fee').text(new Intl.NumberFormat('vi-VN').format(totalFee) + ' đ');
            },
            error: function(xhr) {
                console.error('Lỗi khi tính phí vận chuyển:', xhr);
                $('#estimated_fee').text('Lỗi tính phí');
            }
        });
    }
    
    // Trigger fee calculation on change
    $('#weight, #service_type, #cod_amount, input[name="pickup_method"]').on('change input', function() {
        calculateShippingFee();
    });
});
</script>
@endpush

