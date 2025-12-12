@extends('admin.layout')

@section('title', 'Quản Lý Tài Xế')
@section('page-title', 'Quản Lý Tài Xế')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="fas fa-user-tie me-2"></i>Danh sách tài xế</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDriverModal">
        <i class="fas fa-plus me-2"></i>Thêm tài xế
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>Mã TX</th>
                        <th>Tên tài xế</th>
                        <th>Điện thoại</th>
                        <th>Khu vực</th>
                        <th>Loại xe</th>
                        <th>Biển số</th>
                        <th>Kho</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drivers ?? [] as $driver)
                    <tr>
                        <td><strong>{{ $driver->code }}</strong></td>
                        <td>{{ $driver->name }}</td>
                        <td>{{ $driver->phone }}</td>
                        <td>{{ $driver->area ?? 'N/A' }}</td>
                        <td>{{ $driver->vehicle_type ?? 'N/A' }}</td>
                        <td>{{ $driver->vehicle_number ?? 'N/A' }}</td>
                        <td>{{ $driver->warehouse->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-{{ $driver->is_active ? 'success' : 'secondary' }}">
                                {{ $driver->is_active ? 'Hoạt động' : 'Tạm dừng' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.drivers.show', $driver->id) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">Chưa có tài xế nào</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Driver Modal -->
<div class="modal fade" id="addDriverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.drivers.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Thêm tài xế mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tên tài xế <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Khu vực phụ trách</label>
                        <input type="text" name="area" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Loại xe</label>
                        <input type="text" name="vehicle_type" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Biển số xe</label>
                        <input type="text" name="vehicle_number" class="form-control">
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

