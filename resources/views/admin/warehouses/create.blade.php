@extends('admin.layout')

@section('title', 'Tạo Kho Mới')
@section('page-title', 'Tạo Kho Mới')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Tạo kho mới</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.warehouses.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Mã kho <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">VD: KHO-HN-001, KHO-SG-001</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Tên kho <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" required>{{ old('address') }}</textarea>
                @error('address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Tỉnh/Thành phố <span class="text-danger">*</span></label>
                        <select name="province" id="province_select" class="form-select @error('province') is-invalid @enderror" required>
                            <option value="">-- Chọn Tỉnh/Thành phố --</option>
                            @foreach($provinces ?? [] as $province)
                                <option value="{{ $province->name }}" data-code="{{ $province->province_code }}" {{ old('province') == $province->name ? 'selected' : '' }}>
                                    {{ $province->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="province_code" id="province_code" value="{{ old('province_code') }}">
                        @error('province')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Phường/Xã</label>
                        <select name="ward" id="ward_select" class="form-select @error('ward') is-invalid @enderror" disabled>
                            <option value="">-- Chọn Tỉnh/Thành phố trước --</option>
                        </select>
                        <input type="hidden" name="ward_code" id="ward_code" value="{{ old('ward_code') }}">
                        @error('ward')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Chọn tỉnh/thành phố để hiển thị danh sách phường/xã</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Tên quản lý (Admin Kho)</label>
                        <input type="text" name="manager_name" id="manager_name" class="form-control" value="{{ old('manager_name') }}" readonly>
                        <small class="text-muted">Sẽ được tự động điền khi chọn Admin Kho bên dưới</small>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Ghi chú</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
            </div>

            <hr class="my-4">
            <h6 class="mb-3"><i class="fas fa-user-shield me-2"></i>Gán Admin Kho <span class="text-danger">*</span></h6>
            <div class="mb-3">
                <label class="form-label">Chọn nhân viên làm Admin Kho</label>
                <select name="admin_user_id" id="admin_user_id" class="form-select @error('admin_user_id') is-invalid @enderror" required>
                    <option value="">-- Chọn nhân viên làm Admin Kho --</option>
                    @foreach($availableUsers ?? [] as $user)
                        <option value="{{ $user->id }}" data-name="{{ $user->name }}" {{ old('admin_user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }}) 
                            @if($user->role)
                                - {{ $user->role }}
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('admin_user_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">
                    Chọn nhân viên để gán làm Admin Kho. Tên quản lý sẽ tự động được điền.
                </small>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.warehouses.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Tạo kho
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const provinceSelect = document.getElementById('province_select');
    const wardSelect = document.getElementById('ward_select');
    const provinceCodeInput = document.getElementById('province_code');
    const wardCodeInput = document.getElementById('ward_code');
    
    const oldWardCode = '{{ old("ward_code") }}';
    const oldProvinceCode = '{{ old("province_code") }}';
    
    // Load wards khi province thay đổi
    if (provinceSelect && wardSelect) {
        provinceSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const provinceCode = selectedOption ? selectedOption.getAttribute('data-code') : null;
            const provinceName = this.value;
            
            provinceCodeInput.value = provinceCode || '';
            
            // Reset ward select
            wardSelect.innerHTML = '<option value="">-- Đang tải...</option>';
            wardSelect.disabled = true;
            wardCodeInput.value = '';
            
            if (provinceCode) {
                // Load wards từ API
                fetch(`{{ route('admin.api.wards') }}?province_code=${provinceCode}`)
                    .then(response => response.json())
                    .then(data => {
                        wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
                        
                        if (data && data.length > 0) {
                            data.forEach(ward => {
                                const option = document.createElement('option');
                                option.value = ward.ward_name;
                                option.setAttribute('data-code', ward.ward_code);
                                option.textContent = ward.ward_name;
                                
                                // Restore old value nếu có
                                if (oldWardCode && ward.ward_code === oldWardCode) {
                                    option.selected = true;
                                    wardCodeInput.value = ward.ward_code;
                                }
                                
                                wardSelect.appendChild(option);
                            });
                        } else {
                            wardSelect.innerHTML = '<option value="">-- Không có dữ liệu --</option>';
                        }
                        
                        wardSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error loading wards:', error);
                        wardSelect.innerHTML = '<option value="">-- Lỗi khi tải dữ liệu --</option>';
                    });
            } else {
                wardSelect.innerHTML = '<option value="">-- Chọn Tỉnh/Thành phố trước --</option>';
            }
        });
        
        // Handle ward select change
        wardSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const wardCode = selectedOption ? selectedOption.getAttribute('data-code') : null;
            wardCodeInput.value = wardCode || '';
        });
        
        // Load wards nếu đã có province được chọn (khi có old value)
        if (oldProvinceCode && provinceSelect.value) {
            provinceSelect.dispatchEvent(new Event('change'));
        }
    }

    // Auto-fill manager name when admin user is selected
    const adminUserSelect = document.getElementById('admin_user_id');
    const managerNameInput = document.getElementById('manager_name');
    
    if (adminUserSelect && managerNameInput) {
        adminUserSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.value) {
                const userName = selectedOption.getAttribute('data-name');
                managerNameInput.value = userName || '';
            } else {
                managerNameInput.value = '';
            }
        });
        
        // Trigger change if already has value (for old input)
        if (adminUserSelect.value) {
            adminUserSelect.dispatchEvent(new Event('change'));
        }
    }
});
</script>
@endpush
@endsection

