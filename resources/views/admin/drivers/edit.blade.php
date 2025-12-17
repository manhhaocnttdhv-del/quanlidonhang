@extends('admin.layout')

@section('title', 'Sửa Tài Xế')
@section('page-title', 'Sửa Tài Xế')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Sửa tài xế: {{ $driver->name }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.drivers.update', $driver->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Tên tài xế <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $driver->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $driver->phone) }}" required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Loại tài xế <span class="text-danger">*</span></label>
                        <select name="driver_type" class="form-select @error('driver_type') is-invalid @enderror" required>
                            <option value="shipper" {{ old('driver_type', $driver->driver_type) == 'shipper' ? 'selected' : '' }}>Tài xế Shipper</option>
                            <option value="intercity_driver" {{ old('driver_type', $driver->driver_type) == 'intercity_driver' ? 'selected' : '' }}>Tài xế vận chuyển tỉnh</option>
                        </select>
                        <small class="text-muted">Shipper: Giao hàng nội thành. Vận chuyển tỉnh: Vận chuyển hàng giữa các tỉnh</small>
                        @error('driver_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Kho</label>
                        @if(auth()->user()->isWarehouseAdmin() && auth()->user()->warehouse)
                            {{-- Warehouse admin: Ẩn dropdown, tự động dùng kho của họ --}}
                            <input type="hidden" name="warehouse_id" value="{{ auth()->user()->warehouse_id }}">
                            <input type="text" class="form-control" value="{{ auth()->user()->warehouse->name }}" disabled>
                            <small class="text-muted">Tài xế thuộc kho của bạn</small>
                        @else
                            {{-- Super admin/Admin: Hiển thị dropdown để chọn --}}
                            <select name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror">
                                <option value="">Chọn kho</option>
                                @foreach($warehouses ?? [] as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $driver->warehouse_id) == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Khu vực phụ trách</label>
                        <input type="text" name="area" class="form-control" value="{{ old('area', $driver->area) }}" placeholder="VD: Thành phố Vinh, Nghệ An hoặc Vận chuyển các tỉnh">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Loại xe</label>
                        <input type="text" name="vehicle_type" class="form-control" value="{{ old('vehicle_type', $driver->vehicle_type) }}" placeholder="VD: Xe máy, Xe tải nhỏ, Xe tải lớn">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Biển số xe</label>
                        <input type="text" name="vehicle_number" class="form-control" value="{{ old('vehicle_number', $driver->vehicle_number) }}" placeholder="VD: 37A-12345">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $driver->email) }}">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Số bằng lái</label>
                        <input type="text" name="license_number" class="form-control" value="{{ old('license_number', $driver->license_number) }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Ghi chú</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $driver->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $driver->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        Kích hoạt tài khoản
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.drivers.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
