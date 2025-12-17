@extends('admin.layout')

@section('title', 'Quản Lý Kho')
@section('page-title', 'Quản Lý Kho')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="fas fa-warehouse me-2"></i>Danh sách kho</h4>
    @if(auth()->user()->canManageWarehouses())
    <a href="{{ route('admin.warehouses.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Thêm kho mới
    </a>
    @endif
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
                <a href="{{ route('admin.warehouses.show', $warehouse->id) }}" class="btn btn-sm btn-primary" title="Xem chi tiết">
                    <i class="fas fa-eye me-1"></i>Xem chi tiết
                </a>
                @if(auth()->user()->canManageWarehouses())
                    @php
                        $hasOrders = $warehouse->orders()->count() > 0;
                        $hasDrivers = $warehouse->drivers()->count() > 0;
                        $hasUsers = $warehouse->users()->count() > 0;
                        $canDelete = !$hasOrders && !$hasDrivers && !$hasUsers;
                    @endphp
                    @if($canDelete)
                    <form action="{{ route('admin.warehouses.destroy', $warehouse->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa kho này?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" title="Xóa">
                            <i class="fas fa-trash me-1"></i>Xóa
                        </button>
                    </form>
                    @endif
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-info">Chưa có kho nào</div>
    </div>
    @endforelse
</div>

@endsection

