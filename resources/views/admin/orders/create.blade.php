@extends('admin.layout')

@section('title', 'Tiếp Nhận Yêu Cầu')
@section('page-title', 'Tiếp Nhận Yêu Cầu')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Tạo đơn hàng mới</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.orders.store') }}" method="POST" id="orderForm">
            @csrf
            
            <div class="row">
                <div class="col-md-6">
                    <h6 class="border-bottom pb-2 mb-3">Thông tin người gửi</h6>
                    <div class="mb-3 position-relative">
                        <label class="form-label">Chọn khách hàng (người gửi)</label>
                        <div class="input-group">
                            <input type="text" id="customer_search" class="form-control" placeholder="Tìm kiếm khách hàng..." autocomplete="off">
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                                <i class="fas fa-plus me-1"></i>Thêm mới
                            </button>
                        </div>
                        <select name="customer_id" id="customer_id" class="form-select mt-2" style="display: none;">
                            <option value="">-- Chọn khách hàng hoặc nhập thủ công --</option>
                            @foreach($customers ?? [] as $customer)
                            <option value="{{ $customer->id }}" 
                                data-name="{{ $customer->name }}"
                                data-phone="{{ $customer->phone }}"
                                data-email="{{ $customer->email }}"
                                data-address="{{ $customer->address }}"
                                data-province="{{ $customer->province }}"
                                data-district="{{ $customer->district }}"
                                data-ward="{{ $customer->ward }}"
                                data-code="{{ $customer->code }}"
                                {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->code }} - {{ $customer->name }} ({{ $customer->phone }})
                            </option>
                            @endforeach
                        </select>
                        <div id="customer_results" class="list-group border rounded mt-1" style="display: none; max-height: 200px; overflow-y: auto; position: absolute; z-index: 1000; width: 100%; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                        <small class="text-muted d-block mt-2">Tìm kiếm hoặc thêm khách hàng mới. Nếu không chọn, có thể nhập thủ công bên dưới.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tên người gửi <span class="text-danger">*</span></label>
                        <input type="text" name="sender_name" id="sender_name" class="form-control" required value="{{ old('sender_name') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                        <input type="text" name="sender_phone" id="sender_phone" class="form-control" required value="{{ old('sender_phone') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                        <textarea name="sender_address" id="sender_address" class="form-control" rows="2" required>{{ old('sender_address') }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <label class="form-label">Tỉnh/Thành</label>
                            <input type="text" name="sender_province" id="sender_province" class="form-control" value="Nghệ An" readonly>
                            <small class="text-muted">Mặc định: Nghệ An (không cần chọn huyện/xã)</small>
                            <input type="hidden" name="sender_district" id="sender_district" value="">
                            <input type="hidden" name="sender_ward" id="sender_ward" value="">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phương thức nhận hàng <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pickup_method" id="pickup_method_driver" value="driver" checked>
                            <label class="form-check-label" for="pickup_method_driver">
                                <i class="fas fa-truck me-1"></i>Tài xế đến lấy hàng
                            </label>
                            <small class="d-block text-muted ms-4">Tài xế sẽ đến địa chỉ người gửi để lấy hàng</small>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="radio" name="pickup_method" id="pickup_method_warehouse" value="warehouse">
                            <label class="form-check-label" for="pickup_method_warehouse">
                                <i class="fas fa-warehouse me-1"></i>Đưa đến kho
                            </label>
                            <small class="d-block text-muted ms-4">Người gửi tự đưa hàng đến kho Nghệ An, đơn hàng sẽ tự động có mặt tại kho</small>
                        </div>
                    </div>
                </div>
                
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
                        <div class="col-md-4">
                            <label class="form-label">Tỉnh/Thành <span class="text-danger">*</span></label>
                            <select name="receiver_province" id="receiver_province" class="form-select address-select" required>
                                <option value="">-- Chọn Tỉnh/Thành --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quận/Huyện <span class="text-danger">*</span></label>
                            <select name="receiver_district" id="receiver_district" class="form-select address-select" disabled required>
                                <option value="">-- Chọn Quận/Huyện --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phường/Xã <span class="text-danger">*</span></label>
                            <select name="receiver_ward" id="receiver_ward" class="form-select address-select" disabled required>
                                <option value="">-- Chọn Phường/Xã --</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Địa chỉ chi tiết <span class="text-danger">*</span></label>
                        <textarea name="receiver_address" id="receiver_address" class="form-control" rows="2" required placeholder="Nhập địa chỉ chi tiết sau khi chọn Tỉnh/Huyện/Xã" disabled>{{ old('receiver_address') }}</textarea>
                        <small class="text-muted">Vui lòng chọn đầy đủ Tỉnh/Huyện/Xã trước khi nhập địa chỉ</small>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <div class="row">
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
                </div>
                
                <div class="col-md-6">
                    <h6 class="border-bottom pb-2 mb-3">Thông tin dịch vụ</h6>
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
                    <div class="alert alert-info">
                        <strong>Phí vận chuyển ước tính:</strong>
                        <div id="estimated_fee" class="h4 mb-0">0 đ</div>
                        <hr class="my-2">
                        <small class="d-block"><strong>Công thức tính:</strong></small>
                        <small class="d-block">Tổng phí = <span id="formula_base">Phí cơ bản</span> + <span id="formula_weight">Phí theo trọng lượng</span> + <span id="formula_cod">Phí COD</span></small>
                        <small class="d-block mt-1">
                            <span id="formula_detail" class="text-muted">Phí cơ bản + (Trọng lượng - Trọng lượng tối thiểu) × Phí/kg + COD × %COD</span>
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Hủy</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Tạo đơn hàng
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addCustomerForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Thêm khách hàng mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tên khách hàng <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Địa chỉ</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Tỉnh/Thành</label>
                            <select name="province" id="modal_province" class="form-select address-select">
                                <option value="">-- Chọn Tỉnh/Thành --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quận/Huyện</label>
                            <select name="district" id="modal_district" class="form-select address-select" disabled>
                                <option value="">-- Chọn Quận/Huyện --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phường/Xã</label>
                            <select name="ward" id="modal_ward" class="form-select address-select" disabled>
                                <option value="">-- Chọn Phường/Xã --</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Thêm khách hàng
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
        
        // Set sender province to Nghệ An (no district/ward needed)
        $('#sender_province').val('Nghệ An');
        
        // Populate receiver province select
        const receiverProvinceSelect = $('#receiver_province');
        receiverProvinceSelect.find('option:not(:first)').remove();
        vietnamAddresses.provinces.forEach(province => {
            receiverProvinceSelect.append(`<option value="${province.name}">${province.name}</option>`);
        });
    }
    
    // Load districts for a province
    function loadDistrictsForProvince(provinceName, districtSelectId) {
        if (!vietnamAddresses) return;
        
        const province = vietnamAddresses.provinces.find(p => p.name === provinceName);
        const $districtSelect = $(districtSelectId);
        
        $districtSelect.prop('disabled', false).html('<option value="">-- Chọn Quận/Huyện --</option>');
        
        if (province && province.districts) {
            province.districts.forEach(district => {
                $districtSelect.append(`<option value="${district.name}">${district.name}</option>`);
            });
        }
    }
    
    
    // Handle receiver province change
    $(document).on('change', '#receiver_province', function() {
        const provinceName = $(this).val();
        const $districtSelect = $('#receiver_district');
        const $wardSelect = $('#receiver_ward');
        const $addressInput = $('#receiver_address');
        
        // Reset and disable district, ward, and address
        $districtSelect.prop('disabled', true).html('<option value="">-- Chọn Quận/Huyện --</option>');
        $wardSelect.prop('disabled', true).html('<option value="">-- Chọn Phường/Xã --</option>');
        $addressInput.prop('disabled', true).val('').attr('placeholder', 'Nhập địa chỉ chi tiết sau khi chọn Tỉnh/Huyện/Xã');
        
        if (!provinceName || !vietnamAddresses) {
            calculateShippingFee();
            return;
        }
        
        loadDistrictsForProvince(provinceName, '#receiver_district');
        
        // Trigger shipping fee calculation
        setTimeout(function() {
            calculateShippingFee();
        }, 100);
    });
    
    // Handle receiver district change
    $(document).on('change', '#receiver_district', function() {
        const districtName = $(this).val();
        const $wardSelect = $('#receiver_ward');
        const $addressInput = $('#receiver_address');
        const $provinceSelect = $('#receiver_province');
        const provinceName = $provinceSelect.val();
        
        // Reset ward and disable address
        $wardSelect.prop('disabled', true).html('<option value="">-- Chọn Phường/Xã --</option>');
        $addressInput.prop('disabled', true).val('').attr('placeholder', 'Nhập địa chỉ chi tiết sau khi chọn Tỉnh/Huyện/Xã');
        
        if (!districtName || !provinceName || !vietnamAddresses) {
            calculateShippingFee();
            return;
        }
        
        const province = vietnamAddresses.provinces.find(p => p.name === provinceName);
        if (province) {
            const district = province.districts.find(d => d.name === districtName);
            if (district && district.wards) {
                $wardSelect.prop('disabled', false);
                district.wards.forEach(ward => {
                    $wardSelect.append(`<option value="${ward.name}">${ward.name}</option>`);
                });
            }
        }
        
        calculateShippingFee();
    });
    
    // Handle receiver ward change - enable address input
    $(document).on('change', '#receiver_ward', function() {
        const wardName = $(this).val();
        const $addressInput = $('#receiver_address');
        
        if (wardName) {
            // Enable address input when ward is selected
            $addressInput.prop('disabled', false).attr('placeholder', 'Nhập địa chỉ chi tiết (số nhà, tên đường, ...)');
        } else {
            $addressInput.prop('disabled', true).val('').attr('placeholder', 'Nhập địa chỉ chi tiết sau khi chọn Tỉnh/Huyện/Xã');
        }
        
        calculateShippingFee();
    });
    
    const customers = [
        @foreach($customers ?? [] as $customer)
        {
            id: {{ $customer->id }},
            code: '{{ $customer->code }}',
            name: '{{ $customer->name }}',
            phone: '{{ $customer->phone }}',
            email: '{{ $customer->email ?? '' }}',
            address: '{{ $customer->address ?? '' }}',
            province: '{{ $customer->province ?? '' }}',
            district: '{{ $customer->district ?? '' }}',
            ward: '{{ $customer->ward ?? '' }}'
        },
        @endforeach
    ];
    
    let selectedCustomerId = null;
    
    // Search customers
    $('#customer_search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        const results = $('#customer_results');
        
        if (searchTerm.length < 2) {
            results.hide().empty();
            return;
        }
        
        const filtered = customers.filter(c => 
            c.name.toLowerCase().includes(searchTerm) ||
            c.phone.includes(searchTerm) ||
            c.code.toLowerCase().includes(searchTerm)
        );
        
        if (filtered.length > 0) {
            let html = '';
            filtered.forEach(customer => {
                html += `
                    <a href="#" class="list-group-item list-group-item-action" data-id="${customer.id}">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">${customer.name}</h6>
                            <small>${customer.code}</small>
                        </div>
                        <p class="mb-1"><i class="fas fa-phone me-1"></i>${customer.phone}</p>
                        ${customer.address ? `<small>${customer.address}</small>` : ''}
                    </a>
                `;
            });
            results.html(html).show();
        } else {
            results.html('<div class="list-group-item text-muted">Không tìm thấy khách hàng</div>').show();
        }
    });
    
    // Select customer from search results
    $(document).on('click', '#customer_results .list-group-item', function(e) {
        e.preventDefault();
        const customerId = $(this).data('id');
        selectCustomer(customerId);
        $('#customer_search').val('');
        $('#customer_results').hide();
    });
    
    // Click outside to close results
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#customer_search, #customer_results').length) {
            $('#customer_results').hide();
        }
    });
    
    // Select customer function
    function selectCustomer(customerId) {
        const customer = customers.find(c => c.id == customerId);
        if (customer) {
            selectedCustomerId = customerId;
            $('#customer_id').val(customerId);
            $('#customer_search').val(`${customer.code} - ${customer.name} (${customer.phone})`);
            
            // Auto-fill form
            $('#sender_name').val(customer.name || '');
            $('#sender_phone').val(customer.phone || '');
            $('#sender_address').val(customer.address || '');
            
            // Set province to Nghệ An (no need for district/ward)
            $('#sender_province').val('Nghệ An');
            
            calculateShippingFee();
        }
    }
    
    // Auto-fill sender information when customer is selected from dropdown
    $('#customer_id').on('change', function() {
        const customerId = $(this).val();
        if (customerId) {
            selectCustomer(customerId);
        }
    });
    
    // Add new customer
    $('#addCustomerForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        $.ajax({
            url: '{{ route("admin.customers.store") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.data || response.customer) {
                    const customer = response.data || response.customer;
                    
                    // Add to customers array
                    customers.push({
                        id: customer.id,
                        code: customer.code,
                        name: customer.name,
                        phone: customer.phone,
                        email: customer.email || '',
                        address: customer.address || '',
                        province: customer.province || '',
                        district: customer.district || '',
                        ward: customer.ward || ''
                    });
                    
                    // Add to dropdown
                    const option = new Option(
                        `${customer.code} - ${customer.name} (${customer.phone})`,
                        customer.id,
                        true,
                        true
                    );
                    option.setAttribute('data-name', customer.name);
                    option.setAttribute('data-phone', customer.phone);
                    option.setAttribute('data-email', customer.email || '');
                    option.setAttribute('data-address', customer.address || '');
                    option.setAttribute('data-province', customer.province || '');
                    option.setAttribute('data-district', customer.district || '');
                    option.setAttribute('data-ward', customer.ward || '');
                    option.setAttribute('data-code', customer.code);
                    $('#customer_id').append(option);
                    
                    // Select the new customer
                    selectCustomer(customer.id);
                    
                    // Close modal
                    $('#addCustomerModal').modal('hide');
                    $('#addCustomerForm')[0].reset();
                    
                    alert('Đã thêm khách hàng thành công!');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Có lỗi xảy ra khi thêm khách hàng';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors).flat().join(', ');
                }
                alert(errorMsg);
            }
        });
    });
    
    function calculateShippingFee() {
        const weight = parseFloat($('#weight').val()) || 0;
        const codAmount = parseFloat($('#cod_amount').val()) || 0;
        const serviceType = $('#service_type').val() || 'standard';
        const fromProvince = 'Nghệ An'; // Always from Nghệ An
        const fromDistrict = $('#sender_district').val() || '';
        const toProvince = $('#receiver_province').val() || '';
        const toDistrict = $('#receiver_district').val() || '';
        
        // Check if receiver province is selected
        if (!toProvince) {
            $('#estimated_fee').text('Vui lòng chọn tỉnh/thành nhận');
            return;
        }
        
        // Check if weight is entered
        if (weight <= 0) {
            $('#estimated_fee').text('Vui lòng nhập trọng lượng');
            return;
        }
        
        // Show loading state
        $('#estimated_fee').text('Đang tính...');
        
        $.ajax({
            url: '{{ route("admin.shipping-fees.calculate") }}',
            method: 'POST',
            data: {
                from_province: fromProvince,
                from_district: fromDistrict,
                to_province: toProvince,
                to_district: toDistrict,
                weight: weight,
                service_type: serviceType,
                cod_amount: codAmount,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.total_fee !== undefined) {
                    const totalFee = response.total_fee;
                    const baseFee = response.base_fee || 0;
                    const weightFee = response.weight_fee || 0;
                    const codFee = response.cod_fee || 0;
                    
                    // Hiển thị tổng phí
                    $('#estimated_fee').text(new Intl.NumberFormat('vi-VN').format(totalFee) + ' đ');
                    
                    // Hiển thị chi tiết công thức
                    $('#formula_base').text(new Intl.NumberFormat('vi-VN').format(baseFee) + ' đ');
                    $('#formula_weight').text(new Intl.NumberFormat('vi-VN').format(weightFee) + ' đ');
                    $('#formula_cod').text(new Intl.NumberFormat('vi-VN').format(codFee) + ' đ');
                    
                    // Hiển thị công thức chi tiết
                    const weight = parseFloat($('#weight').val()) || 0;
                    const codAmount = parseFloat($('#cod_amount').val()) || 0;
                    let detail = `${new Intl.NumberFormat('vi-VN').format(baseFee)} đ (phí cơ bản)`;
                    if (weightFee > 0) {
                        detail += ` + ${new Intl.NumberFormat('vi-VN').format(weightFee)} đ (${weight}kg × phí/kg)`;
                    }
                    if (codFee > 0) {
                        detail += ` + ${new Intl.NumberFormat('vi-VN').format(codFee)} đ (COD ${new Intl.NumberFormat('vi-VN').format(codAmount)} đ × ${response.cod_fee_percent || 0}%)`;
                    }
                    detail += ` = ${new Intl.NumberFormat('vi-VN').format(totalFee)} đ`;
                    $('#formula_detail').text(detail);
                } else if (response.estimated_fee) {
                    $('#estimated_fee').text(new Intl.NumberFormat('vi-VN').format(response.estimated_fee) + ' đ (ước tính)');
                    $('#formula_base').text('N/A');
                    $('#formula_weight').text('N/A');
                    $('#formula_cod').text('N/A');
                    $('#formula_detail').text('Sử dụng phí ước tính mặc định');
                } else {
                    $('#estimated_fee').text('Không tính được phí');
                    $('#formula_base').text('N/A');
                    $('#formula_weight').text('N/A');
                    $('#formula_cod').text('N/A');
                    $('#formula_detail').text('Không tìm thấy bảng cước phù hợp');
                }
            },
            error: function(xhr) {
                console.error('Lỗi tính phí:', xhr);
                let errorMsg = 'Lỗi tính phí';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                $('#estimated_fee').text(errorMsg);
            }
        });
    }
    
    $('#weight, #cod_amount, #service_type, #receiver_province, #receiver_district, #receiver_ward').on('change', calculateShippingFee);
});
</script>
@endpush

