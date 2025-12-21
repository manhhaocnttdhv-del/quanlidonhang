<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đăng Ký Tài Khoản - SmartPost</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Setup CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border: none;
            border-radius: 15px;
        }
        
        .card-header {
            border-radius: 15px 15px 0 0 !important;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Đăng Ký Tài Khoản</h4>
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
                    
                    <form method="POST" action="{{ route('customer.register.submit') }}" id="registerForm">
                        @csrf
                        
                        <h6 class="border-bottom pb-2 mb-3">Thông tin cá nhân</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required placeholder="0987654321">
                                <small class="text-muted">Số điện thoại sẽ được dùng để đăng nhập</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="email@example.com">
                                <small class="text-muted">Email có thể dùng để đăng nhập (tùy chọn)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" name="password_confirmation" class="form-control" required minlength="6">
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6 class="border-bottom pb-2 mb-3">Địa chỉ</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tỉnh/Thành <span class="text-danger">*</span></label>
                                <select name="province" id="province" class="form-select" required>
                                    <option value="">-- Chọn Tỉnh/Thành --</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phường/Xã <span class="text-danger">*</span></label>
                                <select name="ward" id="ward" class="form-select" required disabled>
                                    <option value="">-- Chọn Tỉnh/Thành trước --</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ chi tiết (Số nhà, tên đường) <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="2" required placeholder="Ví dụ: 123 Đường ABC, Phường XYZ">{{ old('address') }}</textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Kho gần nhất</label>
                            <select name="warehouse_id" id="warehouse_id" class="form-select" disabled>
                                <option value="">-- Chọn Tỉnh/Thành trước --</option>
                            </select>
                            <small class="text-muted">Kho sẽ được tự động chọn dựa trên tỉnh bạn chọn</small>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('login') }}" class="text-muted">
                                <i class="fas fa-arrow-left me-1"></i>Đã có tài khoản? Đăng nhập
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Đăng Ký
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery (must load before Bootstrap) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Check if jQuery is loaded
if (typeof jQuery === 'undefined') {
    console.error('jQuery is not loaded!');
    alert('Lỗi: jQuery chưa được tải. Vui lòng làm mới trang.');
} else {
    console.log('jQuery version:', jQuery.fn.jquery);
}

$(document).ready(function() {
    // Load Vietnam addresses data (giống như trong quản lý vận đơn)
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
        
        // Populate province select
        const provinceSelect = $('#province');
        provinceSelect.find('option:not(:first)').remove();
        vietnamAddresses.provinces.forEach(province => {
            provinceSelect.append(`<option value="${province.name}">${province.name}</option>`);
        });
        
        // Restore old values if any
        @if(old('province'))
        provinceSelect.val('{{ old('province') }}').trigger('change');
        @endif
    }
    
    // Handle province change - copy y hệt từ quản lý vận đơn
    $(document).on('change', '#province', function() {
        const provinceName = $(this).val();
        const $wardSelect = $('#ward');
        const $warehouseSelect = $('#warehouse_id');
        
        console.log('Province changed event triggered, province:', provinceName);
        console.log('vietnamAddresses loaded:', !!vietnamAddresses);
        
        // Reset and disable ward and warehouse
        $wardSelect.prop('disabled', true).html('<option value="">-- Chọn Tỉnh/Thành trước --</option>');
        $warehouseSelect.prop('disabled', true).html('<option value="">-- Chọn Tỉnh/Thành trước --</option>');
        
        if (!provinceName || !vietnamAddresses) {
            console.warn('Missing province or vietnamAddresses not loaded');
            return;
        }
        
        // Load wards for this province
        loadWardsForProvince(provinceName);
        
        // Load warehouses for this province
        loadWarehousesForProvince(provinceName);
    });
    
    // Load wards for a province (load all wards from all districts) - giống như trong quản lý vận đơn
    function loadWardsForProvince(provinceName) {
        if (!vietnamAddresses) return;
        
        const $wardSelect = $('#ward');
        $wardSelect.prop('disabled', true).html('<option value="">Đang tải...</option>');
        
        const province = vietnamAddresses.provinces.find(p => p.name === provinceName);
        
        if (province && province.districts) {
            // Collect all wards from all districts in this province
            let allWards = [];
            province.districts.forEach(district => {
                if (district.wards && district.wards.length > 0) {
                    district.wards.forEach(ward => {
                        // Avoid duplicates
                        if (!allWards.find(w => w.name === ward.name)) {
                            allWards.push(ward);
                        }
                    });
                }
            });
            
            // Sort wards by name
            allWards.sort((a, b) => a.name.localeCompare(b.name));
            
            $wardSelect.html('<option value="">-- Chọn Phường/Xã --</option>');
            allWards.forEach(ward => {
                $wardSelect.append(`<option value="${ward.name}">${ward.name}</option>`);
            });
            
            $wardSelect.prop('disabled', false);
            
            // Restore old value if any
            @if(old('ward'))
            $wardSelect.val('{{ old('ward') }}');
            @endif
        } else {
            $wardSelect.html('<option value="">Không có dữ liệu</option>');
            $wardSelect.prop('disabled', true);
        }
    }
    
    // Load warehouses for a province - copy y hệt từ quản lý vận đơn
    function loadWarehousesForProvince(provinceName) {
        const $warehouseSelect = $('#warehouse_id');
        
        if (!provinceName) {
            $warehouseSelect.prop('disabled', true).html('<option value="">-- Chọn Tỉnh/Thành trước --</option>');
            return;
        }
        
        // Show loading
        $warehouseSelect.prop('disabled', true).html('<option value="">Đang tải kho...</option>');
        
        console.log('Đang tải kho cho tỉnh:', provinceName);
        console.log('API URL:', '/customer/api/warehouses');
        
        $.ajax({
            url: '/customer/api/warehouses',
            method: 'GET',
            data: { province: provinceName },
            success: function(response) {
                console.log('API Response:', response);
                console.log('Response type:', typeof response);
                
                // Đảm bảo response là array
                let warehouses = Array.isArray(response) ? response : [];
                
                console.log('Kết quả tìm kho:', warehouses);
                console.log('Số lượng kho tìm được:', warehouses.length);
                $warehouseSelect.html('<option value="">-- Chọn kho (tùy chọn) --</option>');
                
                if (warehouses.length === 0) {
                    $warehouseSelect.append('<option value="">Không có kho nào ở tỉnh này</option>');
                    $warehouseSelect.prop('disabled', true);
                    console.warn('Không tìm thấy kho nào cho tỉnh:', provinceName);
                } else {
                    warehouses.forEach(function(warehouse) {
                        $warehouseSelect.append(`<option value="${warehouse.id}">${warehouse.code} - ${warehouse.name} (${warehouse.address})</option>`);
                    });
                    $warehouseSelect.prop('disabled', false);
                    console.log('Đã load', warehouses.length, 'kho');
                }
            },
            error: function(xhr) {
                console.error('Lỗi khi tải danh sách kho:', xhr);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                $warehouseSelect.html('<option value="">Lỗi khi tải danh sách kho</option>');
                $warehouseSelect.prop('disabled', true);
            }
        });
    }
    
    // Form validation
    $('#registerForm').on('submit', function(e) {
        const province = $('#province').val();
        const ward = $('#ward').val();
        const address = $('textarea[name="address"]').val();
        
        if (!province || !ward || !address) {
            e.preventDefault();
            alert('Vui lòng điền đầy đủ thông tin địa chỉ (Tỉnh/Thành, Phường/Xã và địa chỉ chi tiết)');
            return false;
        }
        
        // Đảm bảo CSRF token tồn tại và được gửi
        let csrfInput = $('input[name="_token"]');
        if (csrfInput.length === 0) {
            // Nếu không có CSRF token input, tạo mới
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            $(this).append(`<input type="hidden" name="_token" value="${csrfToken}">`);
        } else {
            // Refresh CSRF token từ meta tag
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            csrfInput.val(csrfToken);
        }
        
        // Cho phép form submit bình thường
        return true;
    });
});
</script>
</body>
</html>

