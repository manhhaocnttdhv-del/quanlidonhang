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
                        <th>Loại tài xế</th>
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
                        <td>
                            @if($driver->driver_type === 'shipper')
                                <span class="badge bg-info">Shipper</span>
                            @elseif($driver->driver_type === 'intercity_driver')
                                <span class="badge bg-warning">Vận chuyển tỉnh</span>
                            @else
                                <span class="badge bg-secondary">N/A</span>
                            @endif
                        </td>
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
                        <td colspan="10" class="text-center">Chưa có tài xế nào</td>
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
                        <label class="form-label">Loại tài xế <span class="text-danger">*</span></label>
                        <select name="driver_type" class="form-select" required>
                            <option value="shipper">Tài xế Shipper</option>
                            <option value="intercity_driver">Tài xế vận chuyển tỉnh</option>
                        </select>
                        <small class="text-muted">Shipper: Giao hàng nội thành. Vận chuyển tỉnh: Vận chuyển hàng giữa các tỉnh</small>
                    </div>
                    <div class="mb-3" id="warehouse_field">
                        <label class="form-label">Kho</label>
                        @if(auth()->user()->isWarehouseAdmin() && auth()->user()->warehouse)
                            {{-- Warehouse admin: Ẩn dropdown, tự động dùng kho của họ --}}
                            <input type="hidden" name="warehouse_id" value="{{ auth()->user()->warehouse_id }}">
                            <input type="text" class="form-control" value="{{ auth()->user()->warehouse->name }}" disabled>
                            <small class="text-muted">Tài xế sẽ được gán vào kho của bạn</small>
                        @else
                            {{-- Super admin/Admin: Hiển thị dropdown để chọn --}}
                            <select name="warehouse_id" class="form-select">
                                <option value="">Chọn kho</option>
                                @foreach($warehouses ?? [] as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Khu vực phụ trách</label>
                        <input type="text" name="area" class="form-control" placeholder="VD: Thành phố Vinh, Nghệ An hoặc Vận chuyển các tỉnh">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Loại xe</label>
                        <input type="text" name="vehicle_type" class="form-control" placeholder="VD: Xe máy, Xe tải nhỏ, Xe tải lớn">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Biển số xe</label>
                        <input type="text" name="vehicle_number" class="form-control" placeholder="VD: 37A-12345">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số bằng lái</label>
                        <input type="text" name="license_number" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi chú</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
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

