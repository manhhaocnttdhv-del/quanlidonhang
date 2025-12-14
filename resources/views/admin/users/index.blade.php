@extends('admin.layout')

@section('title', 'Quản Lý Nhân Viên')
@section('page-title', 'Quản Lý Nhân Viên')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="fas fa-users me-2"></i>Danh sách nhân viên</h4>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Thêm nhân viên
    </a>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Tên, email, SĐT...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Vai trò</label>
                <select name="role" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="super_admin" {{ request('role') == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    <option value="warehouse_admin" {{ request('role') == 'warehouse_admin' ? 'selected' : '' }}>Admin Kho</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Kho</label>
                <select name="warehouse_id" class="form-select">
                    <option value="">Tất cả kho</option>
                    @foreach($warehouses ?? [] as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Tìm kiếm
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên</th>
                        <th>Email</th>
                        <th>SĐT</th>
                        <th>Vai trò</th>
                        <th>Kho</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users ?? [] as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td><strong>{{ $user->name }}</strong></td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone ?? 'N/A' }}</td>
                        <td>
                            @if($user->role === 'super_admin')
                                <span class="badge bg-danger">Super Admin</span>
                            @elseif($user->role === 'admin')
                                <span class="badge bg-primary">Admin</span>
                            @elseif($user->role === 'warehouse_admin')
                                <span class="badge bg-info">Admin Kho</span>
                                @if($user->warehouse)
                                    <br><small class="text-muted">({{ $user->warehouse->name }})</small>
                                @endif
                            @elseif($user->role === 'manager')
                                <span class="badge bg-warning">Quản lý</span>
                            @elseif($user->role === 'dispatcher')
                                <span class="badge bg-secondary">Điều phối</span>
                            @else
                                <span class="badge bg-secondary">{{ $user->role }}</span>
                            @endif
                        </td>
                        <td>
                            @if($user->role === 'warehouse_admin' && $user->warehouse)
                                <div>
                                    <span class="badge bg-info">Admin Kho:</span><br>
                                    <strong>{{ $user->warehouse->name }}</strong><br>
                                    <small class="text-muted">{{ $user->warehouse->province }}</small>
                                </div>
                            @elseif($user->warehouse)
                                <span class="badge bg-success">{{ $user->warehouse->name }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $user->is_active ? 'success' : 'secondary' }}">
                                {{ $user->is_active ? 'Hoạt động' : 'Tạm dừng' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($user->id !== auth()->id())
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa nhân viên này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">Không có nhân viên nào</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($users) && $users->hasPages())
        <div class="mt-3">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

