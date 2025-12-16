@extends('admin.layout')

@section('title', 'Sửa Đơn Hàng')
@section('page-title', 'Sửa Đơn Hàng')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Sửa đơn hàng: <strong>{{ $order->tracking_number }}</strong></h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.orders.update', $order->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Thông tin người gửi</h6>
                            <div class="mb-3">
                                <label class="form-label">Tên người gửi <span class="text-danger">*</span></label>
                                <input type="text" name="sender_name" class="form-control" required value="{{ old('sender_name', $order->sender_name) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" name="sender_phone" class="form-control" required value="{{ old('sender_phone', $order->sender_phone) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                                <textarea name="sender_address" class="form-control" rows="2" required>{{ old('sender_address', $order->sender_address) }}</textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Thông tin người nhận</h6>
                            <div class="mb-3">
                                <label class="form-label">Tên người nhận <span class="text-danger">*</span></label>
                                <input type="text" name="receiver_name" class="form-control" required value="{{ old('receiver_name', $order->receiver_name) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" name="receiver_phone" class="form-control" required value="{{ old('receiver_phone', $order->receiver_phone) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                                <textarea name="receiver_address" class="form-control" rows="2" required>{{ old('receiver_address', $order->receiver_address) }}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Thông tin hàng hóa</h6>
                            <div class="mb-3">
                                <label class="form-label">Trọng lượng (kg) <span class="text-danger">*</span></label>
                                <input type="number" name="weight" class="form-control" step="0.01" min="0" required value="{{ old('weight', $order->weight) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Loại hàng</label>
                                <input type="text" name="item_type" class="form-control" value="{{ old('item_type', $order->item_type) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">COD (đ)</label>
                                <input type="number" name="cod_amount" class="form-control" min="0" value="{{ old('cod_amount', $order->cod_amount) }}">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Thông tin vận chuyển</h6>
                            <div class="mb-3">
                                <label class="form-label">Loại dịch vụ</label>
                                <select name="service_type" class="form-select">
                                    <option value="express" {{ old('service_type', $order->service_type) === 'express' ? 'selected' : '' }}>Express</option>
                                    <option value="standard" {{ old('service_type', $order->service_type) === 'standard' ? 'selected' : '' }}>Standard</option>
                                    <option value="economy" {{ old('service_type', $order->service_type) === 'economy' ? 'selected' : '' }}>Economy</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hàng dễ vỡ</label>
                                <div class="form-check">
                                    <input type="checkbox" name="is_fragile" class="form-check-input" value="1" {{ old('is_fragile', $order->is_fragile) ? 'checked' : '' }}>
                                    <label class="form-check-label">Đánh dấu là hàng dễ vỡ</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ghi chú</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $order->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-secondary">Hủy</a>
                        <button type="submit" class="btn btn-primary">Cập nhật đơn hàng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

