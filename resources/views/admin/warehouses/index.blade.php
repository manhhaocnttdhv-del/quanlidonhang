@extends('admin.layout')

@section('title', 'Quản Lý Kho')
@section('page-title', 'Quản Lý Kho')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="fas fa-warehouse me-2"></i>Danh sách kho</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWarehouseModal">
        <i class="fas fa-plus me-2"></i>Thêm kho mới
    </button>
</div>

<div class="row">
    @forelse($warehouses ?? [] as $warehouse)
    <div class="col-md-4 mb-4">
        <div class="card h-100 {{ ($warehouse->province ?? '') === 'Nghệ An' ? 'border-primary border-2' : '' }}">
            <div class="card-header d-flex justify-content-between align-items-center {{ ($warehouse->province ?? '') === 'Nghệ An' ? 'bg-primary text-white' : '' }}">
                <h6 class="mb-0">
                    {{ $warehouse->name }}
                    @if(($warehouse->province ?? '') === 'Nghệ An')
                        <span class="badge bg-light text-primary ms-2">Kho mặc định</span>
                    @endif
                </h6>
                <span class="badge bg-{{ $warehouse->is_active ? 'success' : 'secondary' }}">
                    {{ $warehouse->is_active ? 'Hoạt động' : 'Tạm dừng' }}
                </span>
            </div>
            <div class="card-body">
                <p class="mb-2"><i class="fas fa-map-marker-alt me-2"></i>{{ $warehouse->address }}</p>
                <p class="mb-2"><i class="fas fa-phone me-2"></i>{{ $warehouse->phone ?? 'N/A' }}</p>
                <p class="mb-2"><i class="fas fa-user me-2"></i>Quản lý: {{ $warehouse->manager_name ?? 'N/A' }}</p>
                <p class="mb-0"><strong>Mã kho:</strong> {{ $warehouse->code }}</p>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.warehouses.show', $warehouse->id) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-eye me-1"></i>Xem chi tiết
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-info">Chưa có kho nào</div>
    </div>
    @endforelse
</div>

<!-- Add Warehouse Modal -->
<div class="modal fade" id="addWarehouseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.warehouses.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Thêm kho mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tên kho <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mã kho <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                        <textarea name="address" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tên quản lý</label>
                        <input type="text" name="manager_name" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

