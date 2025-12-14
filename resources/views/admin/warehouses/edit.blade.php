@extends('admin.layout')

@section('title', 'Sửa Kho')
@section('page-title', 'Sửa Thông Tin Kho')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Sửa thông tin kho</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.warehouses.update', $warehouse->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Mã kho <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $warehouse->code) }}" required readonly>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Mã kho không thể thay đổi</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Tên kho <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $warehouse->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" required>{{ old('address', $warehouse->address) }}</textarea>
                @error('address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Tỉnh/Thành phố</label>
                        <select name="province" id="province_select_edit" class="form-select @error('province') is-invalid @enderror">
                            <option value="">-- Chọn Tỉnh/Thành phố --</option>
                            @foreach($provinces ?? [] as $province)
                                <option value="{{ $province->name }}" data-code="{{ $province->province_code }}" {{ old('province', $warehouse->province) == $province->name ? 'selected' : '' }}>
                                    {{ $province->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="province_code" id="province_code_edit" value="{{ old('province_code') }}">
                        @error('province')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Phường/Xã</label>
                        <select name="ward" id="ward_select_edit" class="form-select @error('ward') is-invalid @enderror" disabled>
                            <option value="">-- Chọn Tỉnh/Thành phố trước --</option>
                        </select>
                        <input type="hidden" name="ward_code" id="ward_code_edit" value="{{ old('ward_code') }}">
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
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $warehouse->phone) }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Tên quản lý</label>
                        <input type="text" name="manager_name" class="form-control" value="{{ old('manager_name', $warehouse->manager_name) }}">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Ghi chú</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $warehouse->notes) }}</textarea>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $warehouse->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        Kho hoạt động
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.warehouses.show', $warehouse->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const provinceSelect = document.getElementById('province_select_edit');
    const wardSelect = document.getElementById('ward_select_edit');
    const provinceCodeInput = document.getElementById('province_code_edit');
    const wardCodeInput = document.getElementById('ward_code_edit');
    
    const oldWardCode = '{{ old("ward_code") }}';
    const oldProvinceCode = '{{ old("province_code") }}';
    const currentProvince = '{{ old("province", $warehouse->province) }}';
    
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
                                const currentWard = '{{ old("ward", $warehouse->ward) }}';
                                if ((oldWardCode && ward.ward_code === oldWardCode) || 
                                    (currentWard && ward.ward_name === currentWard)) {
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
        
        // Load wards nếu đã có province được chọn
        if (currentProvince && provinceSelect.value) {
            provinceSelect.dispatchEvent(new Event('change'));
        }
    }
});
</script>
@endpush
@endsection

