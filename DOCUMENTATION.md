# TÀI LIỆU HỆ THỐNG QUẢN LÝ ĐƠN HÀNG

## MỤC LỤC
1. [Tổng quan hệ thống](#tổng-quan-hệ-thống)
2. [Nghiệp vụ chính](#nghiệp-vụ-chính)
3. [Cấu trúc Database](#cấu-trúc-database)
4. [Models và Relationships](#models-và-relationships)
5. [Controllers chính](#controllers-chính)
6. [Flow xử lý đơn hàng](#flow-xử-lý-đơn-hàng)
7. [Routes và Endpoints](#routes-và-endpoints)
8. [Views chính](#views-chính)

---

## TỔNG QUAN HỆ THỐNG

Hệ thống quản lý đơn hàng vận chuyển với các chức năng:
- Quản lý đơn hàng (tạo, sửa, xóa, hủy)
- Quản lý kho (nhập kho, xuất kho, chuyển kho)
- Phân công tài xế (shipper, tài xế vận chuyển tỉnh)
- Giao hàng (thành công, thất bại, trả hàng)
- Báo cáo (theo ngày, theo kho, tổng hợp)

### Vai trò người dùng:
- **Super Admin**: Quản lý toàn bộ hệ thống
- **Admin**: Quản lý đơn hàng, kho, tài xế
- **Warehouse Admin**: Quản lý kho của mình

---

## NGHIỆP VỤ CHÍNH

### 1. Tạo đơn hàng

**Luồng xử lý:**
1. Người dùng nhập thông tin người gửi, người nhận
2. Chọn phương thức nhận hàng:
   - **Driver**: Tài xế đến lấy hàng → Status: `pending`
   - **Warehouse**: Đưa đến kho → Status: `in_warehouse`
3. Hệ thống tự động:
   - Tạo mã vận đơn (VD + YYYYMMDD + 6 ký tự ngẫu nhiên)
   - Xác định kho gửi (dựa trên tỉnh người gửi hoặc kho của warehouse admin)
   - Tính phí vận chuyển (dựa trên khoảng cách, trọng lượng, loại dịch vụ)
4. Lưu vào database với status ban đầu

**File liên quan:**
- `app/Http/Controllers/OrderController.php::store()`
- `resources/views/admin/orders/create.blade.php`

### 2. Nhận hàng vào kho

**Luồng xử lý:**
1. Đơn hàng được tài xế lấy từ người gửi
2. Tài xế đưa hàng về kho
3. Kho nhận hàng:
   - Tạo `WarehouseTransaction` type `in`
   - Cập nhật status: `in_warehouse`
   - Ghi nhận ngày nhận hàng

**File liên quan:**
- `app/Http/Controllers/WarehouseController.php::receiveOrder()`
- `app/Http/Controllers/WarehouseController.php::bulkReceiveOrder()`

### 3. Xuất kho - Chuyển đến kho khác

**Luồng xử lý:**
1. Kho gửi chọn đơn hàng cần chuyển
2. Chọn kho đích (`to_warehouse_id`)
3. Phân công tài xế vận chuyển tỉnh (intercity_driver)
4. Hệ thống:
   - Tạo `WarehouseTransaction` type `out` ở kho gửi
   - Cập nhật status: `in_transit`
   - Lưu `to_warehouse_id`, `delivery_driver_id`

**File liên quan:**
- `app/Http/Controllers/WarehouseController.php::shipToWarehouse()`
- `app/Http/Controllers/WarehouseController.php::bulkReleaseOrder()`

### 4. Nhận hàng từ kho khác

**Luồng xử lý:**
1. Tài xế vận chuyển tỉnh đưa hàng đến kho đích
2. Kho đích nhận hàng:
   - Tạo `WarehouseTransaction` type `in` với notes "Nhận từ {kho gửi}"
   - Cập nhật `warehouse_id` = kho đích
   - Lưu `previous_warehouse_id` = kho gửi
   - Cập nhật status: `in_warehouse` (hoặc `out_for_delivery` nếu cùng tỉnh và có shipper)

**File liên quan:**
- `app/Http/Controllers/WarehouseController.php::receiveOrder()`
- `app/Http/Controllers/DeliveryController.php::index()` - Phần "Đơn hàng đã nhận từ kho khác"

### 5. Phân công shipper giao hàng

**Luồng xử lý:**
1. Kho chọn đơn hàng cần giao (status: `in_warehouse`)
2. Chọn shipper (driver_type = 'shipper')
3. Hệ thống:
   - Tạo `WarehouseTransaction` type `out`
   - Cập nhật status: `out_for_delivery`
   - Lưu `delivery_driver_id` = shipper được chọn

**File liên quan:**
- `app/Http/Controllers/DeliveryController.php::assignDeliveryDriver()`
- `app/Http/Controllers/DeliveryController.php::bulkAssignDeliveryDriver()`

### 6. Giao hàng thành công

**Luồng xử lý:**
1. Shipper giao hàng thành công
2. Cập nhật trạng thái:
   - Status: `delivered`
   - `delivered_at`: Thời gian giao hàng
   - `cod_collected`: COD đã thu (nếu có)
   - `shipping_fee`: Phí vận chuyển đã thu (mặc định 30,000 đ)
3. Tạo `OrderStatus` với status `delivered`
4. Tính doanh thu: `cod_collected + shipping_fee`

**File liên quan:**
- `app/Http/Controllers/DeliveryController.php::updateDeliveryStatus()`
- `resources/views/admin/delivery/index.blade.php` - Function `updateDeliveryStatus()`

### 7. Giao hàng thất bại

**Luồng xử lý:**
1. Shipper báo giao hàng thất bại
2. Cập nhật:
   - Status: `failed`
   - `failure_reason`: Lý do thất bại
3. Tạo `OrderStatus` với status `failed`
4. Nếu chọn "Trả về kho":
   - Tạo `WarehouseTransaction` type `in`
   - Cập nhật `warehouse_id` = kho hiện tại
   - Status: `in_warehouse`

**File liên quan:**
- `app/Http/Controllers/DeliveryController.php::updateDeliveryStatus()`
- `app/Http/Controllers/DeliveryController.php::returnToWarehouse()`

### 8. Hủy đơn hàng

**Luồng xử lý:**
1. Người dùng hủy đơn hàng (chỉ khi status chưa `delivered` hoặc `cancelled`)
2. Hệ thống tìm kho gửi ban đầu:
   - Ưu tiên: `previous_warehouse_id`
   - Nếu không có: Tìm transaction `out` đầu tiên
   - Nếu không có: Dùng `warehouse_id` hiện tại
3. Cập nhật:
   - Status: `cancelled`
   - `warehouse_id` = kho gửi ban đầu
   - `previous_warehouse_id` = null
   - `to_warehouse_id` = null
   - `delivery_driver_id` = null
   - `pickup_driver_id` = null
   - `failure_reason` = Lý do hủy
4. Tạo `OrderStatus` với status `cancelled`
5. Tạo `WarehouseTransaction` type `in` với notes "Đơn hàng bị hủy - Quay lại kho {tên kho}"

**File liên quan:**
- `app/Http/Controllers/OrderController.php::cancelOrder()`
- `resources/views/admin/orders/show.blade.php` - Modal hủy đơn hàng

### 9. Trả hàng (Return Order)

**Luồng xử lý:**
1. Đơn hàng bị hủy hoặc giao thất bại → Quay lại kho
2. Kho gán shipper trả hàng:
   - Chọn shipper (driver_type = 'shipper')
   - Nhập phí trả hàng (mặc định 30,000 đ)
   - Status: `out_for_delivery`
   - Lưu `return_fee`
3. Shipper trả hàng thành công:
   - Status: `delivered`
   - Cập nhật `return_fee` = phí đã thu
   - Hiển thị badge "Đã hủy" + "Đã giao (trả hàng)"
4. Tính doanh thu: `cod_collected + shipping_fee + return_fee`

**File liên quan:**
- `app/Http/Controllers/DeliveryController.php::assignReturnShipper()`
- `app/Http/Controllers/DeliveryController.php::updateDeliveryStatus()` - Xử lý return_fee
- `resources/views/admin/delivery/index.blade.php` - Phần "Đơn hàng đã nhận từ kho khác"

---

## CẤU TRÚC DATABASE

### Bảng chính:

#### 1. `orders` - Đơn hàng
```sql
- id: Primary key
- tracking_number: Mã vận đơn (VD + YYYYMMDD + 6 ký tự)
- customer_id: ID khách hàng (nullable)
- sender_name, sender_phone, sender_address: Thông tin người gửi
- receiver_name, receiver_phone, receiver_address: Thông tin người nhận
- warehouse_id: Kho hiện tại
- previous_warehouse_id: Kho gửi ban đầu (dùng cho trả hàng)
- to_warehouse_id: Kho đích (khi chuyển kho)
- pickup_driver_id: Tài xế lấy hàng
- delivery_driver_id: Tài xế giao hàng
- status: Trạng thái (pending, in_warehouse, in_transit, out_for_delivery, delivered, failed, cancelled)
- cod_amount: COD
- cod_collected: COD đã thu
- shipping_fee: Phí vận chuyển
- return_fee: Phí trả hàng (nullable)
- delivered_at: Ngày giao hàng
- failure_reason: Lý do thất bại/hủy
```

#### 2. `warehouse_transactions` - Giao dịch kho
```sql
- id: Primary key
- warehouse_id: Kho
- order_id: Đơn hàng
- type: Loại (in: nhập kho, out: xuất kho)
- notes: Ghi chú (ví dụ: "Nhận từ kho Hà Nội", "Trả đơn hàng")
- transaction_date: Ngày giao dịch
- created_by: Người tạo
```

#### 3. `order_statuses` - Lịch sử trạng thái
```sql
- id: Primary key
- order_id: Đơn hàng
- status: Trạng thái
- notes: Ghi chú
- warehouse_id: Kho (khi thay đổi trạng thái)
- updated_by: Người cập nhật
- created_at: Thời gian
```

---

## MODELS VÀ RELATIONSHIPS

### Order Model (`app/Models/Order.php`)

**Relationships:**
```php
- customer(): BelongsTo Customer
- warehouse(): BelongsTo Warehouse (kho hiện tại)
- previousWarehouse(): BelongsTo Warehouse (kho gửi ban đầu)
- toWarehouse(): BelongsTo Warehouse (kho đích)
- pickupDriver(): BelongsTo Driver (tài xế lấy hàng)
- deliveryDriver(): BelongsTo Driver (tài xế giao hàng)
- statuses(): HasMany OrderStatus (lịch sử trạng thái)
- warehouseTransactions(): HasMany WarehouseTransaction
```

**Accessors:**
```php
- from_warehouse: Lấy kho gửi (từ previous_warehouse_id hoặc transaction 'out' đầu tiên)
```

**Fillable:**
```php
- tracking_number, customer_id
- sender_name, sender_phone, sender_address, sender_province, sender_district
- receiver_name, receiver_phone, receiver_address, receiver_province, receiver_district
- warehouse_id, previous_warehouse_id, to_warehouse_id
- pickup_driver_id, delivery_driver_id
- status, cod_amount, cod_collected, shipping_fee, return_fee
- delivered_at, failure_reason
```

### WarehouseTransaction Model

**Relationships:**
```php
- warehouse(): BelongsTo Warehouse
- order(): BelongsTo Order
- createdBy(): BelongsTo User
```

**Types:**
- `in`: Nhập kho (từ tài xế, từ kho khác, trả hàng)
- `out`: Xuất kho (cho shipper, chuyển đến kho khác)

---

## CONTROLLERS CHÍNH

### 1. OrderController (`app/Http/Controllers/OrderController.php`)

**Chức năng:**
- Quản lý CRUD đơn hàng
- Cập nhật trạng thái đơn hàng
- Hủy đơn hàng

**Methods quan trọng:**

#### `store(Request $request)` - Tạo đơn hàng
```php
// Validation
- Thông tin người gửi, người nhận
- pickup_method: 'driver' hoặc 'warehouse'
- to_warehouse_id: Kho đích (nullable)

// Logic:
1. Generate tracking_number
2. Xác định warehouse_id (từ user hoặc tỉnh người gửi)
3. Tính shipping_fee
4. Set initial status:
   - pickup_method = 'warehouse' → status = 'in_warehouse'
   - pickup_method = 'driver' → status = 'pending'
5. Tạo Order
6. Tạo OrderStatus đầu tiên
```

#### `update(Request $request, string $id)` - Cập nhật đơn hàng
```php
// Validation
- Tương tự store() nhưng sender_district, receiver_district là nullable
- pickup_method: 'driver' hoặc 'warehouse'

// Logic:
1. Validate dữ liệu
2. Tính lại shipping_fee
3. Cập nhật warehouse_id (dựa trên tỉnh người gửi)
4. Loại bỏ status và pickup_method khỏi orderData (không thay đổi khi update)
5. Update order
```

#### `cancelOrder(Request $request, string $id)` - Hủy đơn hàng
```php
// Validation
- cancellation_reason: required
- notes: nullable

// Logic:
1. Kiểm tra status (không cho hủy nếu đã delivered hoặc cancelled)
2. Tìm previous_warehouse_id:
   - Ưu tiên: order->previous_warehouse_id
   - Nếu không: Tìm transaction 'out' đầu tiên
   - Nếu không: Dùng warehouse_id hiện tại
3. DB::transaction:
   - Update order: status = 'cancelled', warehouse_id = previous_warehouse_id
   - Nullify: to_warehouse_id, delivery_driver_id, pickup_driver_id
   - Tạo OrderStatus với status 'cancelled'
   - Tạo WarehouseTransaction type 'in' với notes "Đơn hàng bị hủy"
```

### 2. WarehouseController (`app/Http/Controllers/WarehouseController.php`)

**Chức năng:**
- Quản lý kho
- Nhận hàng vào kho
- Xuất kho (chuyển đến kho khác, giao cho shipper)

**Methods quan trọng:**

#### `receiveOrder(Request $request)` - Nhận hàng vào kho
```php
// Validation
- order_id: required
- warehouse_id: required (hoặc lấy từ user nếu là warehouse admin)
- from_warehouse_id: nullable (kho gửi)

// Logic:
1. Tìm kho gửi (từ from_warehouse_id hoặc detectFromWarehouse())
2. Xác định status cuối:
   - Nếu cùng tỉnh và có shipper → status = 'out_for_delivery', giữ delivery_driver_id
   - Nếu không → status = 'in_warehouse', delivery_driver_id = null
3. DB::transaction:
   - Update order: warehouse_id, previous_warehouse_id, status, delivery_driver_id
   - Tạo WarehouseTransaction type 'in' với notes "Nhận từ {kho gửi}"
```

#### `shipToWarehouse(Request $request)` - Chuyển đến kho khác
```php
// Validation
- order_ids: array of order IDs
- to_warehouse_id: required

// Logic:
1. Validate tất cả đơn hàng (status phải in_warehouse)
2. Phân công tài xế vận chuyển tỉnh (intercity_driver)
3. DB::transaction cho mỗi đơn:
   - Tạo WarehouseTransaction type 'out'
   - Update order: status = 'in_transit', to_warehouse_id, delivery_driver_id
```

#### `detectFromWarehouse(Order $order, int $warehouseId)` - Tìm kho gửi
```php
// Logic tìm kho gửi (theo thứ tự ưu tiên):
1. previous_warehouse_id (nếu có)
2. warehouse_id (nếu khác với warehouseId)
3. Transaction 'out' đầu tiên (warehouse_id khác warehouseId)
```

### 3. DeliveryController (`app/Http/Controllers/DeliveryController.php`)

**Chức năng:**
- Quản lý giao hàng
- Phân công shipper
- Cập nhật trạng thái giao hàng (thành công, thất bại)
- Trả hàng

**Methods quan trọng:**

#### `index(Request $request)` - Trang giao hàng
```php
// Lấy các danh sách đơn hàng:
1. ordersShippedOut: Đơn hàng đã xuất từ kho (chuyển đến kho khác)
2. ordersIncoming: Đơn hàng đang đến kho
3. ordersReadyForDelivery: Đơn hàng sẵn sàng giao (in_warehouse, cùng tỉnh)
4. ordersReceivedFromWarehouses: Đơn hàng đã nhận từ kho khác
5. ordersFailed: Đơn hàng thất bại
```

#### `assignDeliveryDriver(Request $request, string $id)` - Phân công shipper
```php
// Validation
- driver_id: required (phải là shipper)
- notes: nullable

// Logic:
1. Kiểm tra order status = 'in_warehouse'
2. Kiểm tra driver type = 'shipper'
3. DB::transaction:
   - Tạo WarehouseTransaction type 'out'
   - Update order: status = 'out_for_delivery', delivery_driver_id
   - Tạo OrderStatus
```

#### `assignReturnShipper(Request $request, string $id)` - Gán shipper trả hàng
```php
// Validation
- driver_id: required (phải là shipper)
- return_fee: nullable (mặc định 30,000 đ)
- notes: nullable

// Logic:
1. Kiểm tra order status = 'in_warehouse'
2. Kiểm tra driver type = 'shipper'
3. DB::transaction:
   - Update order: status = 'out_for_delivery', delivery_driver_id, return_fee
   - Tạo OrderStatus với notes "Gán shipper trả hàng"
```

#### `updateDeliveryStatus(Request $request, string $id)` - Cập nhật trạng thái giao hàng
```php
// Validation
- status: 'delivered' hoặc 'failed'
- cod_collected: nullable (khi delivered)
- shipping_fee: nullable (khi delivered, đơn hàng thường)
- return_fee_collected: nullable (khi delivered, đơn trả về)
- failure_reason: required_if:status,failed

// Logic khi status = 'delivered':
1. Kiểm tra có phải đơn trả về không (return_fee > 0):
   - Nếu có: Cập nhật return_fee = return_fee_collected (mặc định 30,000)
   - Nếu không: Cập nhật cod_collected và shipping_fee (mặc định 30,000)
2. Update order: delivered_at, cod_collected, shipping_fee hoặc return_fee
3. Tạo OrderStatus với status 'delivered'

// Logic khi status = 'failed':
1. Update order: failure_reason
2. Tạo OrderStatus với status 'failed'
3. Nếu chọn "Trả về kho": Gọi returnToWarehouse()
```

#### `returnToWarehouse(Request $request, string $id)` - Trả hàng về kho
```php
// Logic:
1. Tìm previous_warehouse_id
2. DB::transaction:
   - Update order: warehouse_id = previous_warehouse_id, status = 'in_warehouse'
   - Tạo WarehouseTransaction type 'in' với notes "Trả đơn hàng thất bại về kho"
```

### 4. ReportController (`app/Http/Controllers/ReportController.php`)

**Chức năng:**
- Báo cáo theo ngày
- Báo cáo theo kho
- Báo cáo tổng hợp tất cả kho
- Export CSV

**Methods quan trọng:**

#### `index(Request $request)` - Báo cáo theo ngày
```php
// Logic:
1. Lấy đơn hàng trong khoảng thời gian (date_from, date_to)
2. Phân loại theo ngày:
   - Đơn hàng tạo trong ngày (created_at)
   - Đơn hàng giao trong ngày (delivered_at)
   - Đơn hàng thất bại/hủy trong ngày (updated_at, status failed/cancelled)
3. Tính toán:
   - total_orders: Tổng đơn hàng
   - delivered_orders: Đơn đã giao
   - failed_orders: Đơn thất bại + hủy
   - total_revenue: cod_collected + shipping_fee + return_fee
```

#### `detail(Request $request)` - Chi tiết báo cáo theo ngày
```php
// Parameters:
- date: Ngày cần xem
- type: 'all', 'delivered', 'failed'

// Logic:
1. Query đơn hàng theo type:
   - delivered: status = 'delivered', delivered_at = date
   - failed: status in ['failed', 'cancelled'], updated_at = date
   - all: Tất cả đơn hàng liên quan đến ngày đó
2. Eager load: customer, deliveryDriver, warehouse, toWarehouse, statuses
3. Filter theo warehouse (nếu là warehouse admin)
```

#### `warehousesOverview(Request $request)` - Báo cáo tổng hợp tất cả kho
```php
// Logic:
1. Lấy tất cả kho active
2. Với mỗi kho, tính:
   - current_inventory: Đơn hàng trong kho (status = 'in_warehouse')
   - incoming_orders: Đơn hàng đang đến (to_warehouse_id = kho này, status = 'in_transit')
   - total_orders: Tổng đơn hàng trong kỳ
   - delivered_orders: Đơn đã giao
   - total_shipping_revenue: Tổng phí vận chuyển
   - total_return_fee: Tổng phí trả hàng
   - in_transactions: Số lần nhập kho
   - out_transactions: Số lần xuất kho
3. Tính tổng hợp tất cả kho
```

#### `warehouseOrders(Request $request, string $warehouseId)` - Chi tiết đơn hàng theo kho
```php
// Parameters:
- warehouseId: ID kho
- date_from, date_to: Khoảng thời gian

// Logic:
1. Query đơn hàng:
   - warehouse_id = warehouseId HOẶC
   - Có transaction liên quan đến kho này
   - created_at trong khoảng thời gian
2. Eager load: customer, deliveryDriver, warehouse, toWarehouse, statuses
3. Filter theo search, status, to_warehouse_id, driver_id (nếu có)
```

#### `exportCSV(Request $request)` - Export CSV
```php
// Parameters:
- export_type: 'detail' hoặc 'warehouse'
- date, date_from, date_to: Tùy theo export_type
- Các filter khác (search, status, warehouse_id, driver_id)

// Logic:
1. Query đơn hàng theo export_type và filters
2. Tạo CSV với BOM UTF-8 (để Excel hiển thị tiếng Việt đúng)
3. Các cột: Mã vận đơn, Người gửi, Người nhận, Kho, Tài xế, COD, Phí VC, Doanh thu, Trạng thái, v.v.
```

---

## FLOW XỬ LÝ ĐƠN HÀNG

### Flow 1: Đơn hàng bình thường (giao trong tỉnh)

```
1. Tạo đơn hàng (pickup_method = 'driver')
   → Status: pending

2. Phân công tài xế lấy hàng
   → Status: pickup_pending → picking_up → picked_up

3. Tài xế đưa hàng về kho
   → WarehouseTransaction type 'in'
   → Status: in_warehouse

4. Phân công shipper giao hàng
   → WarehouseTransaction type 'out'
   → Status: out_for_delivery

5. Shipper giao hàng thành công
   → Status: delivered
   → Lưu cod_collected, shipping_fee
```

### Flow 2: Đơn hàng chuyển kho

```
1. Tạo đơn hàng tại kho A
   → Status: in_warehouse

2. Kho A xuất kho, chuyển đến kho B
   → WarehouseTransaction type 'out' (kho A)
   → Status: in_transit
   → to_warehouse_id = kho B
   → delivery_driver_id = tài xế vận chuyển tỉnh

3. Kho B nhận hàng
   → WarehouseTransaction type 'in' (kho B) với notes "Nhận từ kho A"
   → Status: in_warehouse
   → warehouse_id = kho B
   → previous_warehouse_id = kho A

4. Kho B phân công shipper
   → Status: out_for_delivery

5. Shipper giao hàng thành công
   → Status: delivered
```

### Flow 3: Đơn hàng trả về

```
1. Đơn hàng bị hủy hoặc giao thất bại
   → Status: cancelled hoặc failed

2. Hủy đơn hàng (nếu chưa delivered)
   → Tìm previous_warehouse_id (kho gửi ban đầu)
   → WarehouseTransaction type 'in' với notes "Đơn hàng bị hủy"
   → Status: cancelled
   → warehouse_id = previous_warehouse_id

3. Gán shipper trả hàng
   → Status: out_for_delivery
   → return_fee = 30,000 đ (mặc định)

4. Shipper trả hàng thành công
   → Status: delivered
   → return_fee = phí đã thu
   → Hiển thị: "Đã hủy" + "Đã giao (trả hàng)"
```

---

## ROUTES VÀ ENDPOINTS

### Orders
```
GET    /admin/orders                    → OrderController::index()
GET    /admin/orders/create            → OrderController::create()
POST   /admin/orders                   → OrderController::store()
GET    /admin/orders/{id}              → OrderController::show()
GET    /admin/orders/{id}/edit         → OrderController::edit()
PUT    /admin/orders/{id}              → OrderController::update()
DELETE /admin/orders/{id}              → OrderController::destroy()
POST   /admin/orders/{id}/cancel       → OrderController::cancelOrder()
POST   /admin/orders/{id}/update-status → OrderController::updateStatus()
```

### Warehouses
```
GET    /admin/warehouses                → WarehouseController::index()
GET    /admin/warehouses/{id}           → WarehouseController::show()
POST   /admin/warehouses/receive-order  → WarehouseController::receiveOrder()
POST   /admin/warehouses/ship-to-warehouse → WarehouseController::shipToWarehouse()
```

### Delivery
```
GET    /admin/delivery                  → DeliveryController::index()
POST   /admin/delivery/assign-driver/{id} → DeliveryController::assignDeliveryDriver()
POST   /admin/delivery/update-status/{id} → DeliveryController::updateDeliveryStatus()
POST   /admin/delivery/assign-return-shipper/{id} → DeliveryController::assignReturnShipper()
```

### Reports
```
GET    /admin/reports                   → ReportController::index()
GET    /admin/reports/detail            → ReportController::detail()
GET    /admin/reports/warehouses-overview → ReportController::warehousesOverview()
GET    /admin/reports/warehouse/{id}/orders → ReportController::warehouseOrders()
GET    /admin/reports/export-csv        → ReportController::exportCSV()
```

---

## VIEWS CHÍNH

### 1. `admin/orders/index.blade.php` - Danh sách đơn hàng
- Hiển thị tất cả đơn hàng với filter (status, tracking_number, customer, date)
- Actions: Xem, Sửa, Hủy, Xóa

### 2. `admin/orders/create.blade.php` - Tạo đơn hàng
- Form nhập thông tin người gửi, người nhận
- Chọn phương thức nhận hàng (driver/warehouse)
- Tự động tính phí vận chuyển

### 3. `admin/orders/edit.blade.php` - Sửa đơn hàng
- Form tương tự create nhưng pre-filled với dữ liệu hiện tại
- Không cho thay đổi status

### 4. `admin/orders/show.blade.php` - Chi tiết đơn hàng
- Hiển thị đầy đủ thông tin đơn hàng
- Lịch sử trạng thái
- Actions: Sửa, Hủy đơn hàng

### 5. `admin/delivery/index.blade.php` - Trang giao hàng
**Các section:**
- **Đơn hàng đã xuất từ kho**: Đơn hàng đang vận chuyển đến kho khác
- **Đơn hàng đang đến kho**: Đơn hàng sắp đến (in_transit)
- **Đơn hàng sẵn sàng giao**: Đơn hàng trong kho, cùng tỉnh, chưa phân công shipper
- **Đơn hàng đang giao**: Đơn hàng đã phân công shipper (out_for_delivery)
- **Đơn hàng đã nhận từ kho khác**: Đơn hàng nhận từ kho khác (bao gồm cả trả hàng)
- **Đơn hàng thất bại**: Đơn hàng giao thất bại

**JavaScript functions:**
- `updateDeliveryStatus()`: Cập nhật trạng thái giao hàng (thành công/thất bại)
- `assignDeliveryDriver()`: Phân công shipper
- `assignReturnShipper()`: Gán shipper trả hàng
- `loadShippersToSelect()`: Load danh sách shipper từ API

### 6. `admin/reports/index.blade.php` - Báo cáo theo ngày
- Thống kê theo ngày (từ ngày - đến ngày)
- Bảng chi tiết: Số đơn, Đã giao, Thất bại, Doanh thu
- Click vào số để xem chi tiết

### 7. `admin/reports/detail.blade.php` - Chi tiết báo cáo
- Danh sách đơn hàng theo ngày và type (all, delivered, failed)
- Filter: Từ ngày - Đến ngày
- Export CSV

### 8. `admin/reports/warehouses-overview.blade.php` - Tổng hợp tất cả kho
- Thống kê tổng hợp
- Bảng chi tiết từng kho
- Click "Xem" để xem chi tiết đơn hàng của kho

### 9. `admin/reports/warehouse-orders.blade.php` - Chi tiết đơn hàng theo kho
- Danh sách đơn hàng của một kho
- Filter: Từ ngày - Đến ngày
- Export CSV

---

## CÁC ĐIỂM QUAN TRỌNG

### 1. Status Management
- Status chỉ thay đổi qua các action cụ thể (giao hàng, hủy, nhận kho)
- Khi update đơn hàng, status KHÔNG được thay đổi
- Mỗi lần thay đổi status đều tạo `OrderStatus` để lưu lịch sử

### 2. Warehouse Transactions
- Mọi hoạt động nhập/xuất kho đều tạo `WarehouseTransaction`
- Type `in`: Nhập kho (từ tài xế, từ kho khác, trả hàng)
- Type `out`: Xuất kho (cho shipper, chuyển đến kho khác)
- Notes quan trọng để phân biệt loại giao dịch

### 3. Previous Warehouse ID
- Dùng để track kho gửi ban đầu
- Quan trọng khi hủy đơn hàng hoặc trả hàng
- Được set khi nhận hàng từ kho khác

### 4. Return Fee
- Chỉ áp dụng cho đơn hàng trả về
- Mặc định 30,000 đ
- Được thu từ người gửi khi shipper trả hàng
- Tính vào doanh thu: `cod_collected + shipping_fee + return_fee`

### 5. Driver Types
- **Shipper**: Giao hàng đến khách hàng (trong tỉnh)
- **Intercity Driver**: Vận chuyển giữa các kho (tỉnh khác)

### 6. Revenue Calculation
- Doanh thu = `cod_collected + shipping_fee + return_fee`
- `cod_collected`: COD đã thu từ người nhận
- `shipping_fee`: Phí vận chuyển đã thu (mặc định 30,000 đ)
- `return_fee`: Phí trả hàng đã thu (mặc định 30,000 đ, chỉ cho đơn trả về)

---

## VÍ DỤ SỬ DỤNG

### Tạo đơn hàng mới:
```php
POST /admin/orders
{
    "sender_name": "Nguyễn Văn A",
    "sender_phone": "0123456789",
    "receiver_name": "Trần Thị B",
    "receiver_phone": "0987654321",
    "pickup_method": "driver",
    "weight": 2.5,
    "cod_amount": 500000
}
→ Tạo đơn hàng với status = 'pending'
```

### Nhận hàng vào kho:
```php
POST /admin/warehouses/receive-order
{
    "order_id": 123,
    "warehouse_id": 1
}
→ Tạo transaction 'in', status = 'in_warehouse'
```

### Phân công shipper:
```php
POST /admin/delivery/assign-driver/123
{
    "driver_id": 5
}
→ Tạo transaction 'out', status = 'out_for_delivery'
```

### Giao hàng thành công:
```php
POST /admin/delivery/update-status/123
{
    "status": "delivered",
    "cod_collected": 500000,
    "shipping_fee": 30000
}
→ Status = 'delivered', lưu cod_collected và shipping_fee
```

---

## LƯU Ý QUAN TRỌNG

1. **Status không được thay đổi khi update đơn hàng**: Luôn `unset($orderData['status'])`
2. **Pickup method không được thay đổi khi update**: Luôn `unset($orderData['pickup_method'])`
3. **District có thể null**: Validation cho phép `sender_district` và `receiver_district` nullable
4. **Return fee mặc định 30,000 đ**: Nếu không nhập, tự động dùng giá trị này
5. **Shipping fee mặc định 30,000 đ**: Nếu không nhập khi giao hàng thành công
6. **Previous warehouse ID**: Quan trọng để track kho gửi ban đầu, dùng cho trả hàng
7. **Warehouse Transactions**: Mọi hoạt động nhập/xuất kho đều phải tạo transaction để audit

---

## KẾT LUẬN

Hệ thống quản lý đơn hàng được thiết kế để:
- Theo dõi đầy đủ vòng đời đơn hàng
- Quản lý kho hiệu quả với transaction log
- Hỗ trợ chuyển kho và trả hàng
- Tính toán doanh thu chính xác
- Báo cáo chi tiết và export CSV

Mọi thay đổi trạng thái đều được ghi lại trong `order_statuses` và `warehouse_transactions` để đảm bảo tính minh bạch và có thể audit.
