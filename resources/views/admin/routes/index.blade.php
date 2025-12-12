@extends('admin.layout')

@section('title', 'Tuyến Vận Chuyển')
@section('page-title', 'Tuyến Vận Chuyển')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="fas fa-map-marked-alt me-2"></i>Danh sách tuyến</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRouteModal">
        <i class="fas fa-plus me-2"></i>Thêm tuyến mới
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>Mã tuyến</th>
                        <th>Tên tuyến</th>
                        <th>Từ</th>
                        <th>Đến</th>
                        <th>Số ngày ước tính</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($routes ?? [] as $route)
                    <tr>
                        <td><strong>{{ $route->code }}</strong></td>
                        <td>{{ $route->name }}</td>
                        <td>{{ $route->from_province }} - {{ $route->from_district }}</td>
                        <td>{{ $route->to_province }} - {{ $route->to_district }}</td>
                        <td>{{ $route->estimated_days }} ngày</td>
                        <td>
                            <span class="badge bg-{{ $route->is_active ? 'success' : 'secondary' }}">
                                {{ $route->is_active ? 'Hoạt động' : 'Tạm dừng' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.routes.show', $route->id) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">Chưa có tuyến nào</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Route Modal -->
<div class="modal fade" id="addRouteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.routes.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Thêm tuyến mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tên tuyến <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tỉnh/Thành gửi</label>
                            <input type="text" name="from_province" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quận/Huyện gửi</label>
                            <input type="text" name="from_district" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tỉnh/Thành nhận</label>
                            <input type="text" name="to_province" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quận/Huyện nhận</label>
                            <input type="text" name="to_district" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số ngày ước tính</label>
                        <input type="number" name="estimated_days" class="form-control" min="1" value="1">
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

