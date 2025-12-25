# BIỂU ĐỒ TUẦN TỰ (SEQUENCE DIAGRAMS) - HỆ THỐNG QUẢN LÝ VẬN CHUYỂN

## 1. BIỂU ĐỒ TUẦN TỰ - TẠO ĐƠN HÀNG

```mermaid
sequenceDiagram
    participant User as Nhân viên
    participant Browser as Browser
    participant Route as Route
    participant Controller as OrderController
    participant Model as Order Model
    participant ShippingFee as ShippingFee Model
    participant OrderStatus as OrderStatus Model
    participant DB as Database

    User->>Browser: Nhập thông tin đơn hàng
    Browser->>Route: POST /admin/orders
    Route->>Controller: store(Request)
    
    Controller->>Controller: Validate dữ liệu
    Controller->>Model: generateTrackingNumber()
    Model->>DB: Kiểm tra mã vận đơn trùng
    DB-->>Model: Kết quả
    Model-->>Controller: Mã vận đơn
    
    Controller->>Controller: Xác định kho tạo đơn
    Controller->>ShippingFee: calculateShippingFee()
    ShippingFee->>DB: Tìm bảng cước phù hợp
    DB-->>ShippingFee: Bảng cước
    ShippingFee-->>Controller: Phí vận chuyển
    
    Controller->>Model: create(orderData)
    Model->>DB: INSERT INTO orders
    DB-->>Model: Order ID
    Model-->>Controller: Order object
    
    Controller->>OrderStatus: create(statusData)
    OrderStatus->>DB: INSERT INTO order_statuses
    DB-->>OrderStatus: Status ID
    
    Controller->>Controller: Tạo warehouse transaction (nếu đưa đến kho)
    
    Controller-->>Browser: Redirect to show order
    Browser-->>User: Hiển thị chi tiết đơn hàng
```

## 2. BIỂU ĐỒ TUẦN TỰ - LẤY HÀNG VÀ NHẬP KHO

```mermaid
sequenceDiagram
    participant Dispatcher as Điều phối
    participant Browser as Browser
    participant Route as Route
    participant Controller as DispatchController
    participant Order as Order Model
    participant Driver as Driver Model
    participant OrderStatus as OrderStatus Model
    participant Warehouse as Warehouse Model
    participant WarehouseTransaction as WarehouseTransaction Model
    participant DB as Database

    Dispatcher->>Browser: Chọn đơn hàng + Tài xế
    Browser->>Route: POST /admin/dispatch/assign-pickup-driver
    Route->>Controller: assignPickupDriver(Request)
    
    Controller->>Controller: Validate dữ liệu
    Controller->>Order: whereIn('id', orderIds)
    Order->>DB: SELECT orders
    DB-->>Order: Danh sách đơn hàng
    Order-->>Controller: Orders collection
    
    Controller->>Driver: findOrFail(driver_id)
    Driver->>DB: SELECT driver
    DB-->>Driver: Driver data
    Driver-->>Controller: Driver object
    
    loop Cho mỗi đơn hàng
        Controller->>Order: update(pickup_driver_id, status)
        Order->>DB: UPDATE orders
        DB-->>Order: Success
        
        Controller->>OrderStatus: create(pickup_pending)
        OrderStatus->>DB: INSERT INTO order_statuses
        DB-->>OrderStatus: Status ID
    end
    
    Controller-->>Browser: Redirect to dispatch index
    Browser-->>Dispatcher: Hiển thị danh sách đã phân công
    
    Note over Driver,DB: Tài xế lấy hàng
    
    Driver->>Browser: Cập nhật: Đã lấy hàng
    Browser->>Route: POST /admin/dispatch/update-pickup-status/{id}
    Route->>Controller: updatePickupStatus(Request, id)
    
    Controller->>Order: findOrFail(id)
    Order->>DB: SELECT order
    DB-->>Order: Order data
    Order-->>Controller: Order object
    
    Controller->>Driver: find(pickup_driver_id) with warehouse
    Driver->>DB: SELECT driver + warehouse
    DB-->>Driver: Driver + Warehouse data
    Driver-->>Controller: Driver with Warehouse
    
    Controller->>Warehouse: getDefaultWarehouse() (nếu cần)
    Warehouse->>DB: SELECT warehouse
    DB-->>Warehouse: Warehouse data
    Warehouse-->>Controller: Warehouse object
    
    Controller->>Order: update(status: in_warehouse, warehouse_id)
    Order->>DB: UPDATE orders
    DB-->>Order: Success
    
    Controller->>WarehouseTransaction: create(type: in)
    WarehouseTransaction->>DB: INSERT INTO warehouse_transactions
    DB-->>WarehouseTransaction: Transaction ID
    
    Controller->>OrderStatus: create(status: in_warehouse)
    OrderStatus->>DB: INSERT INTO order_statuses
    DB-->>OrderStatus: Status ID
    
    Controller-->>Browser: Redirect
    Browser-->>Driver: Xác nhận đã nhập kho
```

## 3. BIỂU ĐỒ TUẦN TỰ - VẬN CHUYỂN GIỮA CÁC KHO

```mermaid
sequenceDiagram
    participant Staff as Nhân viên kho
    participant Browser as Browser
    participant Route as Route
    participant Controller as WarehouseController
    participant Order as Order Model
    participant Warehouse as Warehouse Model
    participant Driver as Driver Model
    participant OrderStatus as OrderStatus Model
    participant WarehouseTransaction as WarehouseTransaction Model
    participant DB as Database

    Staff->>Browser: Chọn đơn hàng + Kho đích + Tài xế
    Browser->>Route: POST /admin/warehouses/ship-to-warehouse
    Route->>Controller: shipToWarehouse(Request)
    
    Controller->>Controller: Validate dữ liệu
    Controller->>Order: whereIn('id', orderIds) where('status', 'in_warehouse')
    Order->>DB: SELECT orders
    DB-->>Order: Danh sách đơn hàng
    Order-->>Controller: Orders collection
    
    Controller->>Warehouse: findOrFail(to_warehouse_id)
    Warehouse->>DB: SELECT warehouse
    DB-->>Warehouse: Warehouse data
    Warehouse-->>Controller: To Warehouse object
    
    Controller->>Warehouse: find(from_warehouse_id)
    Warehouse->>DB: SELECT warehouse
    DB-->>Warehouse: Warehouse data
    Warehouse-->>Controller: From Warehouse object
    
    alt Có tài xế vận chuyển tỉnh
        Controller->>Driver: find(intercity_driver_id)
        Driver->>DB: SELECT driver
        DB-->>Driver: Driver data
        Driver->>Controller: isIntercityDriver()
        Driver-->>Controller: True/False
    end
    
    loop Cho mỗi đơn hàng
        Controller->>Order: update(status: in_transit, to_warehouse_id)
        Order->>DB: UPDATE orders
        DB-->>Order: Success
        
        Controller->>OrderStatus: create(status: in_transit)
        OrderStatus->>DB: INSERT INTO order_statuses
        DB-->>OrderStatus: Status ID
        
        Controller->>WarehouseTransaction: create(type: out)
        WarehouseTransaction->>DB: INSERT INTO warehouse_transactions
        DB-->>WarehouseTransaction: Transaction ID
    end
    
    Controller-->>Browser: Redirect với thông báo
    Browser-->>Staff: Xác nhận đã xuất kho
    
    Note over Driver,DB: Tài xế vận chuyển tỉnh vận chuyển hàng
    
    Note over Staff,DB: Kho đích nhận hàng
    
    Staff->>Browser: Chọn đơn hàng đã đến
    Browser->>Route: POST /admin/warehouses/receive-order
    Route->>Controller: receiveOrder(Request)
    
    Controller->>Order: findOrFail(order_id)
    Order->>DB: SELECT order
    DB-->>Order: Order data
    Order-->>Controller: Order object
    
    Controller->>Controller: Phát hiện kho gửi (từ warehouse_id hoặc transaction)
    
    Controller->>Warehouse: find(warehouse_id)
    Warehouse->>DB: SELECT warehouse
    DB-->>Warehouse: Warehouse data
    Warehouse-->>Controller: Warehouse object
    
    Controller->>Order: update(warehouse_id: kho đích, status: in_warehouse, to_warehouse_id: null)
    Order->>DB: UPDATE orders
    DB-->>Order: Success
    
    Controller->>WarehouseTransaction: create(type: in)
    WarehouseTransaction->>DB: INSERT INTO warehouse_transactions
    DB-->>WarehouseTransaction: Transaction ID
    
    Controller->>OrderStatus: create(status: in_warehouse)
    OrderStatus->>DB: INSERT INTO order_statuses
    DB-->>OrderStatus: Status ID
    
    Controller-->>Browser: Redirect với thông báo
    Browser-->>Staff: Xác nhận đã nhập kho
```

## 4. BIỂU ĐỒ TUẦN TỰ - GIAO HÀNG CHO KHÁCH HÀNG

```mermaid
sequenceDiagram
    participant Dispatcher as Điều phối
    participant Browser as Browser
    participant Route as Route
    participant Controller as DeliveryController
    participant Order as Order Model
    participant Driver as Driver Model
    participant OrderStatus as OrderStatus Model
    participant WarehouseTransaction as WarehouseTransaction Model
    participant DB as Database

    Dispatcher->>Browser: Chọn đơn hàng sẵn sàng giao
    Browser->>Route: POST /admin/delivery/assign-driver/{id}
    Route->>Controller: assignDeliveryDriver(Request, id)
    
    Controller->>Controller: Validate dữ liệu
    Controller->>Order: findOrFail(id)
    Order->>DB: SELECT order
    DB-->>Order: Order data
    Order-->>Controller: Order object
    
    Controller->>Driver: findOrFail(driver_id)
    Driver->>DB: SELECT driver
    DB-->>Driver: Driver data
    Driver->>Controller: Driver object
    
    Controller->>Driver: isShipper()
    Driver-->>Controller: True
    
    Controller->>Order: update(status: out_for_delivery, delivery_driver_id)
    Order->>DB: UPDATE orders
    DB-->>Order: Success
    
    Controller->>OrderStatus: create(status: out_for_delivery)
    OrderStatus->>DB: INSERT INTO order_statuses
    DB-->>OrderStatus: Status ID
    
    Controller-->>Browser: Redirect
    Browser-->>Dispatcher: Xác nhận đã phân công
    
    Note over Driver,DB: Tài xế shipper giao hàng
    
    Driver->>Browser: Cập nhật: Đã giao hàng
    Browser->>Route: POST /admin/delivery/update-status/{id}
    Route->>Controller: updateDeliveryStatus(Request, id)
    
    Controller->>Order: findOrFail(id)
    Order->>DB: SELECT order
    DB-->>Order: Order data
    Order-->>Controller: Order object
    
    alt Giao hàng thành công
        Controller->>Order: update(status: delivered, delivered_at, cod_collected)
        Order->>DB: UPDATE orders
        DB-->>Order: Success
        
        Controller->>OrderStatus: create(status: delivered)
        OrderStatus->>DB: INSERT INTO order_statuses
        DB-->>OrderStatus: Status ID
    else Giao hàng thất bại
        Controller->>Order: update(status: failed, failure_reason)
        Order->>DB: UPDATE orders
        DB-->>Order: Success
        
        Controller->>OrderStatus: create(status: failed)
        OrderStatus->>DB: INSERT INTO order_statuses
        DB-->>OrderStatus: Status ID
    end
    
    Controller-->>Browser: Redirect
    Browser-->>Driver: Xác nhận đã cập nhật
```

## 5. BIỂU ĐỒ TUẦN TỰ - TẠO BẢNG KÊ COD

```mermaid
sequenceDiagram
    participant Staff as Nhân viên
    participant Browser as Browser
    participant Route as Route
    participant Controller as CodReconciliationController
    participant CodReconciliation as CodReconciliation Model
    participant Order as Order Model
    participant DB as Database

    Staff->>Browser: Chọn khách hàng + Khoảng thời gian + Đơn hàng
    Browser->>Route: POST /admin/cod-reconciliations
    Route->>Controller: store(Request)
    
    Controller->>Controller: Validate dữ liệu
    
    Controller->>Order: whereIn('id', order_ids) where('status', 'delivered')
    Order->>DB: SELECT orders
    DB-->>Order: Danh sách đơn hàng đã giao
    Order-->>Controller: Orders collection
    
    Controller->>Controller: Tính tổng COD, Phí vận chuyển, Tổng tiền
    Controller->>Controller: generateReconciliationNumber()
    Controller->>CodReconciliation: where('reconciliation_number', number)
    CodReconciliation->>DB: SELECT cod_reconciliations
    DB-->>CodReconciliation: Kết quả
    CodReconciliation-->>Controller: Mã bảng kê
    
    Controller->>CodReconciliation: create(reconciliationData)
    CodReconciliation->>DB: INSERT INTO cod_reconciliations
    DB-->>CodReconciliation: Reconciliation ID
    CodReconciliation-->>Controller: CodReconciliation object
    
    loop Cho mỗi đơn hàng
        Controller->>CodReconciliation: orders()->attach(order_id, pivotData)
        CodReconciliation->>DB: INSERT INTO cod_reconciliation_orders
        DB-->>CodReconciliation: Success
    end
    
    Controller-->>Browser: Redirect to show reconciliation
    Browser-->>Staff: Hiển thị chi tiết bảng kê
```

## 6. BIỂU ĐỒ TUẦN TỰ - CẬP NHẬT THANH TOÁN COD

```mermaid
sequenceDiagram
    participant Staff as Nhân viên
    participant Browser as Browser
    participant Route as Route
    participant Controller as CodReconciliationController
    participant CodReconciliation as CodReconciliation Model
    participant DB as Database

    Staff->>Browser: Nhập số tiền đã thanh toán
    Browser->>Route: POST /admin/cod-reconciliations/{id}/update-payment
    Route->>Controller: updatePayment(Request, id)
    
    Controller->>Controller: Validate dữ liệu (paid_amount)
    
    Controller->>CodReconciliation: findOrFail(id)
    CodReconciliation->>DB: SELECT cod_reconciliation
    DB-->>CodReconciliation: Reconciliation data
    CodReconciliation-->>Controller: CodReconciliation object
    
    Controller->>Controller: Tính số tiền còn lại
    Controller->>Controller: Xác định trạng thái (partial/paid)
    
    Controller->>CodReconciliation: update(paid_amount, remaining_amount, status)
    CodReconciliation->>DB: UPDATE cod_reconciliations
    DB-->>CodReconciliation: Success
    
    Controller-->>Browser: Redirect back
    Browser-->>Staff: Hiển thị cập nhật thành công
```

## 7. BIỂU ĐỒ TUẦN TỰ - TÍNH PHÍ VẬN CHUYỂN

```mermaid
sequenceDiagram
    participant Controller as OrderController
    participant ShippingFee as ShippingFee Model
    participant DB as Database

    Controller->>Controller: calculateShippingFee(fromProvince, toProvince, weight, serviceType, codAmount)
    
    Note over Controller,DB: Priority 1: Tìm khớp chính xác (tỉnh + quận)
    Controller->>ShippingFee: where(from_province, to_province, from_district, to_district, service_type)
    ShippingFee->>DB: SELECT shipping_fees
    DB-->>ShippingFee: ShippingFee hoặc null
    ShippingFee-->>Controller: Result
    
    alt Không tìm thấy
        Note over Controller,DB: Priority 2: Tìm theo tỉnh
        Controller->>ShippingFee: where(from_province, to_province, service_type) whereNull('from_district')
        ShippingFee->>DB: SELECT shipping_fees
        DB-->>ShippingFee: ShippingFee hoặc null
        ShippingFee-->>Controller: Result
    end
    
    alt Không tìm thấy
        Note over Controller,DB: Priority 3: Tìm theo vùng (Bắc/Trung/Nam)
        Controller->>Controller: getRegion(fromProvince)
        Controller->>Controller: getRegion(toProvince)
        Controller->>Controller: getRegionalFee() hoặc getInterRegionalFee()
        Controller-->>Controller: Fee structure
    end
    
    alt Không tìm thấy
        Note over Controller,DB: Priority 4: Phí mặc định
        Controller->>Controller: Default fee structure
    end
    
    Controller->>Controller: Tính toán: base_fee + weight_fee + cod_fee
    Controller-->>Controller: Tổng phí vận chuyển
```

## 8. BIỂU ĐỒ TUẦN TỰ - XEM BÁO CÁO

```mermaid
sequenceDiagram
    participant User as Người dùng
    participant Browser as Browser
    participant Route as Route
    participant Controller as ReportController
    participant Order as Order Model
    participant WarehouseTransaction as WarehouseTransaction Model
    participant DB as Database

    User->>Browser: Chọn khoảng thời gian
    Browser->>Route: GET /admin/reports
    Route->>Controller: index(Request)
    
    Controller->>Controller: Lấy dateFrom, dateTo từ request
    
    alt Warehouse Admin
        Controller->>Controller: Lấy warehouse_id của user
    end
    
    Controller->>Order: whereBetween('created_at', [dateFrom, dateTo])
    Order->>DB: SELECT orders
    DB-->>Order: Orders collection
    Order-->>Controller: Orders
    
    alt Warehouse Admin
        Controller->>Controller: Lọc đơn hàng theo warehouse_id
    end
    
    Controller->>Controller: Nhóm đơn hàng theo ngày
    Controller->>Controller: Tính toán: total_orders, delivered_orders, failed_orders, revenue
    
    Controller->>Order: whereDate('created_at', today()) where('status', 'delivered')
    Order->>DB: SELECT orders
    DB-->>Order: Delivered orders today
    Order-->>Controller: Delivered orders
    
    Controller->>Controller: Tính dailyStats
    
    Controller-->>Browser: View với reportData và dailyStats
    Browser-->>User: Hiển thị báo cáo
```

## 9. BIỂU ĐỒ TUẦN TỰ - TRA CỨU ĐƠN HÀNG

```mermaid
sequenceDiagram
    participant User as Người dùng
    participant Browser as Browser
    participant Route as Route
    participant Controller as TrackingController
    participant Order as Order Model
    participant OrderStatus as OrderStatus Model
    participant DB as Database

    User->>Browser: Nhập mã vận đơn
    Browser->>Route: POST /admin/tracking
    Route->>Controller: track(Request)
    
    Controller->>Controller: Validate tracking_number
    
    Controller->>Order: where('tracking_number', tracking_number)
    Order->>DB: SELECT order
    DB-->>Order: Order data hoặc null
    Order-->>Controller: Order object hoặc null
    
    alt Tìm thấy đơn hàng
        Controller->>Order: with(['statuses', 'customer', 'warehouse'])
        Order->>DB: SELECT order + relationships
        DB-->>Order: Order with relationships
        Order-->>Controller: Order object
        
        Controller->>OrderStatus: where('order_id', order_id) orderBy('created_at', 'desc')
        OrderStatus->>DB: SELECT order_statuses
        DB-->>OrderStatus: Statuses collection
        OrderStatus-->>Controller: Statuses
        
        Controller-->>Browser: View với order và statuses
        Browser-->>User: Hiển thị thông tin đơn hàng và timeline
    else Không tìm thấy
        Controller-->>Browser: View với error message
        Browser-->>User: Hiển thị thông báo không tìm thấy
    end
```

## 10. BIỂU ĐỒ TUẦN TỰ - XUẤT KHO CHO SHIPPER

```mermaid
sequenceDiagram
    participant Staff as Nhân viên kho
    participant Browser as Browser
    participant Route as Route
    participant Controller as WarehouseController
    participant Order as Order Model
    participant RouteModel as Route Model
    participant WarehouseTransaction as WarehouseTransaction Model
    participant DB as Database

    Staff->>Browser: Chọn đơn hàng + Tuyến
    Browser->>Route: POST /admin/warehouses/bulk-release-order
    Route->>Controller: bulkReleaseOrder(Request)
    
    Controller->>Controller: Validate dữ liệu
    
    Controller->>Order: whereIn('id', order_ids) where('status', 'in_warehouse')
    Order->>DB: SELECT orders
    DB-->>Order: Danh sách đơn hàng
    Order-->>Controller: Orders collection
    
    Controller->>Controller: Xác định warehouse_id
    
    loop Cho mỗi đơn hàng
        alt Chưa chọn tuyến
            Controller->>RouteModel: where('from_province', warehouse->province) where('to_province', order->receiver_province)
            RouteModel->>DB: SELECT routes
            DB-->>RouteModel: Route hoặc null
            RouteModel-->>Controller: Route object hoặc null
        end
        
        alt Có tuyến
            Controller->>Order: update(route_id)
            Order->>DB: UPDATE orders
            DB-->>Order: Success
        end
        
        Controller->>Order: update(to_warehouse_id: null)
        Order->>DB: UPDATE orders
        DB-->>Order: Success
        
        Controller->>WarehouseTransaction: create(type: out)
        WarehouseTransaction->>DB: INSERT INTO warehouse_transactions
        DB-->>WarehouseTransaction: Transaction ID
    end
    
    Controller-->>Browser: Redirect to delivery index
    Browser-->>Staff: Xác nhận đã xuất kho, chuyển đến trang giao hàng
```
