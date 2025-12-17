@extends('admin.layout')

@section('title', 'Chi Tiết Tài Xế')
@section('page-title', 'Chi Tiết Tài Xế')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Thông tin tài xế: {{ $driver->name }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Mã tài xế</th>
                        <td><strong>{{ $driver->code }}</strong></td>
                    </tr>
                    <tr>
                        <th>Tên tài xế</th>
                        <td>{{ $driver->name }}</td>
                    </tr>
                    <tr>
                        <th>Số điện thoại</th>
                        <td>{{ $driver->phone }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $driver->email ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Loại tài xế</th>
                        <td>
                            @if($driver->driver_type === 'shipper')
                                <span class="badge bg-info">Shipper</span>
                            @elseif($driver->driver_type === 'intercity_driver')
                                <span class="badge bg-warning">Vận chuyển tỉnh</span>
                            @else
                                <span class="badge bg-secondary">N/A</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Kho</th>
                        <td>{{ $driver->warehouse->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Trạng thái</th>
                        <td>
                            <span class="badge bg-{{ $driver->is_active ? 'success' : 'secondary' }}">
                                {{ $driver->is_active ? 'Hoạt động' : 'Tạm dừng' }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Khu vực phụ trách</th>
                        <td>{{ $driver->area ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Loại xe</th>
                        <td>{{ $driver->vehicle_type ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Biển số xe</th>
                        <td>{{ $driver->vehicle_number ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Số bằng lái</th>
                        <td>{{ $driver->license_number ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Ngày tạo</th>
                        <td>{{ $driver->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Cập nhật lần cuối</th>
                        <td>{{ $driver->updated_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Ghi chú</th>
                        <td>{{ $driver->notes ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <h6>Thống kê đơn hàng</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title">Đơn hàng đã lấy</h5>
                            <p class="card-text h3">{{ $driver->pickupOrders->count() }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title">Đơn hàng đã giao</h5>
                            <p class="card-text h3">{{ $driver->deliveryOrders->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('admin.drivers.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Quay lại
            </a>
            <div>
                <a href="{{ route('admin.drivers.edit', $driver->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit me-2"></i>Sửa
                </a>
                @php
                    $hasOrders = $driver->pickupOrders->count() > 0 || $driver->deliveryOrders->count() > 0;
                @endphp
                @if(!$hasOrders)
                <form action="{{ route('admin.drivers.destroy', $driver->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài xế này?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Xóa
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
