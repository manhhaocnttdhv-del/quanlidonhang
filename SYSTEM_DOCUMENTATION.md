# Hệ Thống Quản Lý Vận Chuyển - SmartPost

## Tổng Quan

Hệ thống quản lý vận chuyển hoàn chỉnh dựa trên Laravel 10, hỗ trợ toàn bộ quy trình từ tiếp nhận đơn hàng đến giao hàng và đối soát COD.

## Cấu Trúc Database

### Các Bảng Chính

1. **customers** - Quản lý khách hàng
2. **orders** - Quản lý vận đơn
3. **order_statuses** - Lịch sử trạng thái đơn hàng
4. **warehouses** - Quản lý kho
5. **warehouse_transactions** - Giao dịch nhập/xuất kho
6. **drivers** - Quản lý tài xế
7. **routes** - Quản lý tuyến vận chuyển
8. **complaints** - Quản lý khiếu nại
9. **shipping_fees** - Bảng cước vận chuyển
10. **cod_reconciliations** - Bảng kê COD
11. **cod_reconciliation_orders** - Chi tiết đơn hàng trong bảng kê

## Các Chức Năng Chính

### 1. Tiếp Nhận Yêu Cầu (OrderController)

**Endpoint:** `POST /api/orders`

- Tạo đơn hàng mới
- Tự động tạo mã vận đơn
- Tính phí vận chuyển tự động
- Tạo trạng thái ban đầu

**Các trạng thái đơn hàng:**
- `pending` - Chờ xử lý
- `pickup_pending` - Chờ lấy hàng
- `picking_up` - Đang lấy hàng
- `picked_up` - Đã lấy hàng
- `in_warehouse` - Đã nhập kho
- `in_transit` - Đang vận chuyển
- `out_for_delivery` - Đang giao hàng
- `delivered` - Đã giao hàng
- `failed` - Giao hàng thất bại
- `returned` - Đã hoàn

### 2. Điều Phối Nhận (DispatchController)

**Endpoints:**
- `GET /api/dispatch/pending-pickups` - Danh sách đơn chờ lấy
- `POST /api/dispatch/assign-pickup-driver` - Phân công tài xế lấy hàng
- `POST /api/dispatch/update-pickup-status/{id}` - Cập nhật trạng thái lấy hàng
- `GET /api/dispatch/available-drivers` - Danh sách tài xế khả dụng

### 3. Quản Lý Vận Đơn (OrderController)

**Endpoints:**
- `GET /api/orders` - Danh sách đơn hàng (có filter)
- `GET /api/orders/{id}` - Chi tiết đơn hàng
- `PUT /api/orders/{id}` - Cập nhật đơn hàng
- `POST /api/orders/{id}/update-status` - Cập nhật trạng thái

### 4. Quản Lý Kho (WarehouseController)

**Endpoints:**
- `GET /api/warehouses` - Danh sách kho
- `POST /api/warehouses` - Tạo kho mới
- `POST /api/warehouses/receive-order` - Nhập kho
- `POST /api/warehouses/release-order` - Xuất kho
- `GET /api/warehouses/{id}/inventory` - Tồn kho
- `GET /api/warehouses/{id}/transactions` - Lịch sử giao dịch

### 5. Giao Hàng (DeliveryController)

**Endpoints:**
- `GET /api/delivery/ready-for-delivery` - Đơn sẵn sàng giao
- `POST /api/delivery/assign-driver/{id}` - Phân công tài xế giao
- `POST /api/delivery/update-status/{id}` - Cập nhật trạng thái giao
- `GET /api/delivery/statistics` - Thống kê tài xế

### 6. Sự Cố - Khiếu Nại (ComplaintController)

**Endpoints:**
- `GET /api/complaints` - Danh sách khiếu nại
- `POST /api/complaints` - Tạo khiếu nại
- `POST /api/complaints/{id}/resolve` - Xử lý khiếu nại

**Loại khiếu nại:**
- `delay` - Chậm giao
- `lost` - Thất lạc
- `wrong_cod` - Sai COD
- `damaged` - Hư hỏng
- `other` - Khác

### 7. Tracking (TrackingController)

**Endpoints:**
- `GET /api/tracking/{trackingNumber}` - Tra cứu công khai
- `POST /api/tracking` - Tra cứu chi tiết (cần auth)

### 8. Tra Cước (ShippingFeeController)

**Endpoints:**
- `GET /api/shipping-fees/calculate` - Tính phí vận chuyển
- `GET /api/shipping-fees` - Danh sách bảng cước
- `POST /api/shipping-fees` - Tạo bảng cước mới

**Công thức tính phí:**
```
Tổng phí = Phí cơ bản + (Trọng lượng - Trọng lượng tối thiểu) × Phí/kg + COD × % phí COD
```

### 9. Báo Cáo (ReportController)

**Endpoints:**
- `GET /api/reports/daily` - Báo cáo ngày
- `GET /api/reports/monthly` - Báo cáo tháng
- `GET /api/reports/driver-performance` - Hiệu suất tài xế
- `GET /api/reports/warehouse` - Báo cáo kho
- `GET /api/reports/revenue` - Báo cáo doanh thu

### 10. Bảng Kê COD (CodReconciliationController)

**Endpoints:**
- `GET /api/cod-reconciliations` - Danh sách bảng kê
- `POST /api/cod-reconciliations` - Tạo bảng kê mới
- `GET /api/cod-reconciliations/{id}` - Chi tiết bảng kê
- `POST /api/cod-reconciliations/{id}/update-payment` - Cập nhật thanh toán

## Cài Đặt và Sử Dụng

### 1. Chạy Migrations

```bash
php artisan migrate
```

### 2. Tạo User và Cấu Hình

- Cập nhật bảng `users` với các trường: `role`, `phone`, `is_active`
- Roles: `admin`, `manager`, `dispatcher`, `warehouse_staff`, `driver`, `staff`

### 3. API Authentication

Sử dụng Laravel Sanctum cho authentication:

```bash
# Login
POST /api/login
{
    "email": "user@example.com",
    "password": "password"
}

# Response sẽ trả về token
# Sử dụng token trong header: Authorization: Bearer {token}
```

## Quy Trình Nghiệp Vụ

### Quy Trình Tạo Đơn Hàng

1. Khách hàng tạo yêu cầu (online/offline)
2. Nhân viên nhập thông tin vào hệ thống
3. Hệ thống tự động:
   - Tạo mã vận đơn
   - Tính phí vận chuyển
   - Tạo trạng thái ban đầu
4. Chuyển sang bước điều phối

### Quy Trình Lấy Hàng

1. Điều phối kiểm tra tuyến giao
2. Phân công tài xế lấy hàng
3. Tài xế cập nhật trạng thái: `picking_up` → `picked_up`
4. Nhập kho

### Quy Trình Giao Hàng

1. Hàng vào kho → quét mã → phân tuyến
2. Xuất kho giao hàng
3. Phân công tài xế giao
4. Tài xế giao hàng và cập nhật:
   - `delivered` - Thành công
   - `failed` - Thất bại (có lý do)
5. Thu COD (nếu có)

### Quy Trình Đối Soát COD

1. Tạo bảng kê theo khách hàng/thời gian
2. Hệ thống tự động tính:
   - Tổng COD
   - Tổng phí vận chuyển
   - Tổng tiền
3. Cập nhật thanh toán
4. Xuất bảng kê

## Lưu Ý

1. **Mã vận đơn**: Tự động tạo theo format `VD{YYYYMMDD}{6 ký tự ngẫu nhiên}`
2. **Mã khách hàng**: Tự động tạo theo format `KH{8 ký tự ngẫu nhiên}`
3. **Mã tài xế**: Tự động tạo theo format `TX{8 ký tự ngẫu nhiên}`
4. **Số ticket khiếu nại**: Tự động tạo theo format `KN{YYYYMMDD}{6 ký tự ngẫu nhiên}`
5. **Số bảng kê**: Tự động tạo theo format `BK{YYYYMMDD}{6 ký tự ngẫu nhiên}`

## API Response Format

Tất cả API trả về JSON với format:

```json
{
    "message": "Thông báo",
    "data": {...}
}
```

Hoặc cho pagination:

```json
{
    "data": [...],
    "current_page": 1,
    "per_page": 20,
    "total": 100
}
```

## Bảo Mật

- Tất cả API (trừ tracking công khai) yêu cầu authentication
- Sử dụng Laravel Sanctum
- Có thể thêm middleware phân quyền theo role

## Mở Rộng

Hệ thống có thể mở rộng thêm:
- Notification system
- SMS/Email alerts
- Mobile app integration
- Real-time tracking với WebSocket
- Advanced analytics dashboard

