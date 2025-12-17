# LUỒNG NGHIỆP VỤ VẬN CHUYỂN GIỮA CÁC KHO

## TỔNG QUAN

Hệ thống hỗ trợ vận chuyển hàng hóa giữa các kho để tối ưu hóa quy trình giao hàng. Đơn hàng có thể được tạo tại một kho và vận chuyển đến kho khác gần với địa điểm người nhận hơn để giảm chi phí và thời gian giao hàng.

---

## CÁC TRƯỜNG DỮ LIỆU QUAN TRỌNG

### Trong bảng `orders`:
- **`warehouse_id`**: Kho hiện tại đang chứa đơn hàng (kho nguồn khi xuất, kho đích khi nhận)
- **`to_warehouse_id`**: Kho đích mà đơn hàng sẽ được vận chuyển đến (NULL nếu giao trực tiếp cho khách hàng)
- **`status`**: Trạng thái đơn hàng
  - `in_warehouse`: Đơn hàng đang trong kho
  - `in_transit`: Đơn hàng đang vận chuyển giữa các kho
  - `out_for_delivery`: Đơn hàng đang được shipper giao cho khách hàng
- **`delivery_driver_id`**: 
  - Khi `status = in_transit`: Là tài xế vận chuyển tỉnh (intercity_driver)
  - Khi `status = out_for_delivery`: Là tài xế shipper giao hàng

### Trong bảng `warehouse_transactions`:
- **`type`**: Loại giao dịch
  - `in`: Nhập kho
  - `out`: Xuất kho
- **`warehouse_id`**: Kho thực hiện giao dịch
- **`order_id`**: Đơn hàng liên quan
- **`notes`**: Ghi chú (ví dụ: "Nhận từ kho X", "Xuất kho đi kho Y")

---

## LUỒNG NGHIỆP VỤ CHI TIẾT

### 🔵 BƯỚC 1: TẠO ĐƠN HÀNG

**Tại kho nguồn (ví dụ: Kho Nghệ An)**

1. **Nhân viên tạo đơn hàng**:
   - Nhập thông tin người gửi/nhận
   - Chọn **kho đích** (`to_warehouse_id`) - kho gần với địa điểm người nhận
   - Hệ thống tự động:
     - Tạo mã vận đơn
     - Tính phí vận chuyển
     - Xác định `warehouse_id` = kho tạo đơn (kho nguồn)
     - Set `to_warehouse_id` = kho đích đã chọn
     - Set `status` = `pending` (nếu tài xế đến lấy) hoặc `in_warehouse` (nếu đưa đến kho)

2. **Dữ liệu sau khi tạo**:
   ```
   warehouse_id = Kho Nghệ An (ID)
   to_warehouse_id = Kho Hà Nội (ID)
   status = in_warehouse (nếu đưa đến kho) hoặc pending (nếu tài xế đến lấy)
   ```

---

### 🟢 BƯỚC 2: LẤY HÀNG VÀ NHẬP KHO NGUỒN

**Tại kho nguồn**

1. **Nếu phương thức nhận = "driver"** (tài xế đến lấy):
   - Điều phối phân công tài xế lấy hàng
   - Tài xế lấy hàng → Cập nhật status: `picked_up`
   - **Hệ thống tự động nhập kho của tài xế**:
     - `status`: `picked_up` → `in_warehouse`
     - `warehouse_id`: Cập nhật = kho của tài xế
     - Tạo `WarehouseTransaction` (type: `in`)
     - Tạo `OrderStatus`

2. **Nếu phương thức nhận = "warehouse"** (đưa đến kho):
   - Đơn hàng tự động vào kho ngay khi tạo
   - `status` = `in_warehouse`
   - `warehouse_id` = kho tạo đơn

**Dữ liệu sau bước 2**:
```
warehouse_id = Kho Nghệ An (ID)
to_warehouse_id = Kho Hà Nội (ID)  ← Vẫn giữ nguyên
status = in_warehouse
```

---

### 🟡 BƯỚC 3: XUẤT KHO ĐI KHO KHÁC

**Tại kho nguồn (Kho Nghệ An)**

1. **Nhân viên kho chọn đơn hàng** cần vận chuyển đến kho khác

2. **Thực hiện "Vận chuyển đến kho khác"** (`shipToWarehouse`):
   - Chọn một hoặc nhiều đơn hàng (phải cùng một kho nguồn)
   - Chọn **kho đích** (`to_warehouse_id`)
   - Chọn **tài xế vận chuyển tỉnh** (`intercity_driver_id`) - tùy chọn
   - Thêm số tham chiếu, ghi chú

3. **Hệ thống tự động xử lý**:
   ```php
   // Cập nhật đơn hàng
   status: in_warehouse → in_transit
   to_warehouse_id: Set = kho đích
   delivery_driver_id: Set = tài xế vận chuyển tỉnh (nếu có)
   warehouse_id: Giữ nguyên = kho nguồn
   
   // Tạo OrderStatus
   status: in_transit
   notes: "Xuất kho từ Kho Nghệ An đi Kho Hà Nội - Tài xế: [Tên tài xế]"
   warehouse_id: Kho nguồn
   driver_id: Tài xế vận chuyển tỉnh
   
   // Tạo WarehouseTransaction
   type: out
   warehouse_id: Kho nguồn
   notes: "Xuất kho từ Kho Nghệ An đi Kho Hà Nội - Tài xế: [Tên tài xế]"
   ```

4. **Dữ liệu sau bước 3**:
   ```
   warehouse_id = Kho Nghệ An (ID)  ← Kho nguồn
   to_warehouse_id = Kho Hà Nội (ID)  ← Kho đích
   status = in_transit  ← Đang vận chuyển
   delivery_driver_id = Tài xế vận chuyển tỉnh (ID)
   ```

---

### 🟠 BƯỚC 4: VẬN CHUYỂN

**Trên đường vận chuyển**

1. **Tài xế vận chuyển tỉnh** nhận hàng từ kho nguồn
2. **Đơn hàng ở trạng thái** `in_transit`
3. **Hệ thống theo dõi**:
   - `warehouse_id` = Kho nguồn (vẫn giữ nguyên)
   - `to_warehouse_id` = Kho đích
   - `status` = `in_transit`
   - `delivery_driver_id` = Tài xế vận chuyển tỉnh

4. **Kho đích có thể xem**:
   - Đơn hàng đang đến kho mình (trong phần "Đơn hàng đang đến")
   - Lọc theo: `to_warehouse_id = kho này` HOẶC `receiver_province = tỉnh của kho`

---

### 🔴 BƯỚC 5: NHẬP KHO ĐÍCH

**Tại kho đích (Kho Hà Nội)**

1. **Nhân viên kho đích nhận hàng** từ tài xế vận chuyển tỉnh

2. **Thực hiện "Nhập kho"** (`receiveOrder`):
   - Chọn đơn hàng
   - Chọn kho gửi (`from_warehouse_id`) - hệ thống tự động phát hiện nếu không chọn
   - Thêm số tham chiếu, ghi chú
   - Có thể nhập hàng loạt (`bulkReceiveOrder`)

3. **Hệ thống tự động phát hiện kho gửi**:
   ```php
   // Logic phát hiện kho gửi:
   if (order->status === 'in_transit') {
       from_warehouse = order->warehouse_id;  // Kho nguồn
   } else {
       // Tìm transaction 'out' gần nhất từ kho khác
       from_warehouse = lastOutTransaction->warehouse_id;
   }
   ```

4. **Hệ thống tự động xử lý**:
   ```php
   // Cập nhật đơn hàng
   warehouse_id: Kho nguồn → Kho đích
   status: in_transit → in_warehouse
   to_warehouse_id: Xóa = NULL (vì đã đến kho đích)
   delivery_driver_id: Giữ nguyên (lưu lịch sử tài xế vận chuyển tỉnh)
   
   // Tạo WarehouseTransaction
   type: in
   warehouse_id: Kho đích
   notes: "Nhận từ Kho Nghệ An"
   
   // Tạo OrderStatus
   status: in_warehouse
   notes: "Đơn hàng từ kho Kho Nghệ An (Nghệ An) vào kho Kho Hà Nội - 
           Tài xế vận chuyển: [Tên tài xế] - 
           Kho đích đã nhận được hàng. Có thể phân công tài xế shipper để giao hàng cho khách hàng."
   warehouse_id: Kho đích
   driver_id: Tài xế vận chuyển tỉnh (lưu lịch sử)
   ```

5. **Dữ liệu sau bước 5**:
   ```
   warehouse_id = Kho Hà Nội (ID)  ← Đã chuyển sang kho đích
   to_warehouse_id = NULL  ← Đã xóa vì đã đến kho đích
   status = in_warehouse  ← Đã nhập kho đích
   delivery_driver_id = Tài xế vận chuyển tỉnh (ID)  ← Lưu lịch sử
   ```

---

### 🟣 BƯỚC 6: XUẤT KHO CHO SHIPPER GIAO HÀNG

**Tại kho đích (Kho Hà Nội)**

1. **Nhân viên kho xuất hàng** cho shipper giao hàng:
   - Chọn đơn hàng (có thể nhiều đơn)
   - Chọn tuyến (route) - tự động tìm nếu chưa chọn
   - Thực hiện "Xuất kho" (`releaseOrder` hoặc `bulkReleaseOrder`)

2. **Hệ thống tự động xử lý**:
   ```php
   // Tạo WarehouseTransaction
   type: out
   warehouse_id: Kho đích
   route_id: Tuyến vận chuyển
   notes: "Đã chuẩn bị xuất kho từ Kho Hà Nội"
   
   // Đơn hàng VẪN ở trạng thái in_warehouse
   // Chờ phân công tài xế shipper ở trang "Giao hàng"
   to_warehouse_id: Đảm bảo = NULL (xuất cho shipper, không đi kho khác)
   ```

3. **Dữ liệu sau bước 6**:
   ```
   warehouse_id = Kho Hà Nội (ID)
   to_warehouse_id = NULL
   status = in_warehouse  ← Vẫn trong kho, chờ phân công shipper
   ```

---

### 🟤 BƯỚC 7: PHÂN CÔNG SHIPPER GIAO HÀNG

**Tại kho đích (Kho Hà Nội)**

1. **Nhân viên phân công tài xế shipper**:
   - Chọn đơn hàng (có thể nhiều đơn)
   - Chọn tài xế shipper (khác với tài xế vận chuyển tỉnh)
   - Đặt lịch giao hàng

2. **Hệ thống tự động xử lý**:
   ```php
   // Cập nhật đơn hàng
   status: in_warehouse → out_for_delivery
   delivery_driver_id: Tài xế vận chuyển tỉnh → Tài xế shipper (ghi đè)
   delivery_scheduled_at: Lịch giao hàng
   
   // Tạo OrderStatus
   status: out_for_delivery
   notes: "Đã phân công tài xế [Tên shipper] giao hàng"
   driver_id: Tài xế shipper
   warehouse_id: Kho đích
   ```

3. **Dữ liệu sau bước 7**:
   ```
   warehouse_id = Kho Hà Nội (ID)
   to_warehouse_id = NULL
   status = out_for_delivery
   delivery_driver_id = Tài xế shipper (ID)  ← Đã ghi đè
   ```

---

### ⚫ BƯỚC 8: GIAO HÀNG CHO KHÁCH HÀNG

**Tài xế shipper giao hàng**

1. **Tài xế shipper** giao hàng cho khách hàng

2. **Cập nhật trạng thái**:
   - **Thành công** (`delivered`):
     ```php
     status: out_for_delivery → delivered
     delivered_at: Thời gian giao hàng
     cod_collected: Số tiền COD đã thu (nếu có)
     
     // Tạo OrderStatus
     status: delivered
     notes: "Đã giao hàng thành công cho khách hàng"
     ```
   
   - **Thất bại** (`failed`):
     ```php
     status: out_for_delivery → failed
     
     // Tạo OrderStatus
     status: failed
     notes: "Giao hàng thất bại - [Lý do]"
     ```

3. **Dữ liệu cuối cùng**:
   ```
   warehouse_id = Kho Hà Nội (ID)  ← Kho cuối cùng xử lý
   to_warehouse_id = NULL
   status = delivered hoặc failed
   delivery_driver_id = Tài xế shipper (ID)
   cod_collected = Số tiền COD đã thu
   ```

---

## SƠ ĐỒ LUỒNG NGHIỆP VỤ

```
┌─────────────────────────────────────────────────────────────┐
│  BƯỚC 1: TẠO ĐƠN HÀNG                                       │
│  Kho Nghệ An                                                 │
│  - warehouse_id = Kho Nghệ An                                 │
│  - to_warehouse_id = Kho Hà Nội                             │
│  - status = pending hoặc in_warehouse                       │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  BƯỚC 2: LẤY HÀNG VÀ NHẬP KHO NGUỒN                        │
│  Kho Nghệ An                                                 │
│  - Tài xế lấy hàng → Nhập kho                               │
│  - warehouse_id = Kho Nghệ An                                │
│  - to_warehouse_id = Kho Hà Nội (giữ nguyên)               │
│  - status = in_warehouse                                     │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  BƯỚC 3: XUẤT KHO ĐI KHO KHÁC                               │
│  Kho Nghệ An                                                 │
│  - Chọn đơn hàng + Kho đích + Tài xế vận chuyển tỉnh        │
│  - warehouse_id = Kho Nghệ An (giữ nguyên)                   │
│  - to_warehouse_id = Kho Hà Nội                             │
│  - status = in_transit                                       │
│  - delivery_driver_id = Tài xế vận chuyển tỉnh              │
│  - Tạo WarehouseTransaction (type: out)                    │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  BƯỚC 4: VẬN CHUYỂN                                         │
│  Trên đường                                                  │
│  - Tài xế vận chuyển tỉnh vận chuyển hàng                   │
│  - warehouse_id = Kho Nghệ An (vẫn giữ nguyên)               │
│  - to_warehouse_id = Kho Hà Nội                             │
│  - status = in_transit                                       │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  BƯỚC 5: NHẬP KHO ĐÍCH                                      │
│  Kho Hà Nội                                                  │
│  - Nhận hàng từ tài xế vận chuyển tỉnh                      │
│  - warehouse_id = Kho Nghệ An → Kho Hà Nội                   │
│  - to_warehouse_id = Kho Hà Nội → NULL                     │
│  - status = in_transit → in_warehouse                        │
│  - delivery_driver_id = Tài xế vận chuyển tỉnh (lưu lịch sử)│
│  - Tạo WarehouseTransaction (type: in)                     │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  BƯỚC 6: XUẤT KHO CHO SHIPPER                                │
│  Kho Hà Nội                                                  │
│  - Xuất hàng cho shipper giao hàng                          │
│  - warehouse_id = Kho Hà Nội                                │
│  - to_warehouse_id = NULL                                   │
│  - status = in_warehouse (vẫn trong kho)                    │
│  - Tạo WarehouseTransaction (type: out)                     │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  BƯỚC 7: PHÂN CÔNG SHIPPER                                  │
│  Kho Hà Nội                                                  │
│  - Phân công tài xế shipper                                 │
│  - warehouse_id = Kho Hà Nội                                │
│  - to_warehouse_id = NULL                                   │
│  - status = in_warehouse → out_for_delivery                  │
│  - delivery_driver_id = Tài xế shipper (ghi đè)             │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  BƯỚC 8: GIAO HÀNG                                          │
│  Tài xế shipper                                              │
│  - Giao hàng cho khách hàng                                 │
│  - status = out_for_delivery → delivered/failed              │
│  - cod_collected = Số tiền COD đã thu                       │
└─────────────────────────────────────────────────────────────┘
```

---

## CÁC TRƯỜNG HỢP ĐẶC BIỆT

### 1. Đơn hàng giao trực tiếp (không qua kho khác)

**Khi tạo đơn hàng**:
- Không chọn `to_warehouse_id` (hoặc để NULL)
- Đơn hàng sẽ được giao trực tiếp từ kho tạo đơn

**Luồng**:
```
Tạo đơn → Nhập kho nguồn → Xuất kho cho shipper → Phân công shipper → Giao hàng
```

### 2. Đơn hàng từ tài xế lấy về nhưng cần chuyển kho

**Luồng**:
```
Tài xế lấy hàng → Nhập kho của tài xế → Xuất kho đi kho khác → ...
```

### 3. Nhập kho hàng loạt

**Kho đích có thể nhận nhiều đơn hàng cùng lúc**:
- Chọn nhiều đơn hàng
- Hệ thống tự động xử lý từng đơn
- Tự động phát hiện kho gửi cho từng đơn

### 4. Xuất kho hàng loạt

**Kho nguồn có thể xuất nhiều đơn hàng cùng lúc**:
- Chọn nhiều đơn hàng (phải cùng một kho)
- Chọn kho đích
- Chọn tài xế vận chuyển tỉnh
- Hệ thống tự động xử lý từng đơn

---

## QUẢN LÝ VÀ THEO DÕI

### 1. Kho nguồn theo dõi

**Trong trang "Giao hàng" → "Đơn hàng đã xuất kho"**:
- Xem đơn hàng đã xuất đi kho khác
- Lọc theo tỉnh người nhận
- Đơn hàng sẽ biến mất khi kho đích đã nhận (có transaction 'in' tại kho đích)

**Trong trang "Kho" → Chi tiết kho**:
- Xem lịch sử xuất kho (WarehouseTransaction type: out)
- Xem đơn hàng đã xuất đi kho khác

### 2. Kho đích theo dõi

**Trong trang "Giao hàng" → "Đơn hàng đang đến"**:
- Xem đơn hàng đang vận chuyển đến kho mình
- Lọc theo tỉnh
- Đơn hàng sẽ biến mất khi đã nhận vào kho

**Trong trang "Kho" → Chi tiết kho**:
- Xem đơn hàng đang đến (status: in_transit, to_warehouse_id = kho này)
- Xem lịch sử nhập kho (WarehouseTransaction type: in)
- Xem đơn hàng nhận từ kho khác

### 3. Timeline đơn hàng

**Mỗi thay đổi trạng thái đều được ghi lại**:
- OrderStatus lưu lại: status, notes, warehouse_id, driver_id, thời gian, người cập nhật
- Có thể xem toàn bộ lịch sử di chuyển của đơn hàng

---

## LƯU Ý QUAN TRỌNG

### 1. Phân biệt tài xế

- **Tài xế vận chuyển tỉnh** (`intercity_driver`):
  - Vận chuyển hàng giữa các kho
  - Được gán vào `delivery_driver_id` khi `status = in_transit`
  - Lưu lịch sử trong OrderStatus khi kho đích nhận hàng

- **Tài xế shipper**:
  - Giao hàng cho khách hàng trong tỉnh
  - Được gán vào `delivery_driver_id` khi `status = out_for_delivery`
  - Ghi đè `delivery_driver_id` của tài xế vận chuyển tỉnh

### 2. Quản lý warehouse_id và to_warehouse_id

- **`warehouse_id`**: Luôn là kho hiện tại đang chứa đơn hàng
- **`to_warehouse_id`**: 
  - Khi `status = in_transit`: Là kho đích sẽ nhận hàng
  - Khi `status = in_warehouse` và `to_warehouse_id = NULL`: Đơn hàng đã đến kho đích hoặc giao trực tiếp
  - Khi `status = out_for_delivery`: Phải là NULL (giao cho khách hàng, không đi kho khác)

### 3. WarehouseTransaction

- **Type `out`**: Ghi nhận xuất kho (có thể xuất đi kho khác hoặc xuất cho shipper)
- **Type `in`**: Ghi nhận nhập kho (có thể nhận từ tài xế lấy hàng hoặc nhận từ kho khác)
- **Notes**: Tự động tạo ghi chú rõ ràng về nguồn/đích

### 4. Phân quyền

- **Warehouse Admin**: Chỉ xem/quản lý đơn hàng của kho mình
- **Super Admin/Admin**: Xem/quản lý tất cả kho
- Kho đích chỉ có thể nhận đơn hàng khi đơn hàng có `to_warehouse_id = kho này` hoặc `receiver_province = tỉnh của kho`

---

## VÍ DỤ THỰC TẾ

### Ví dụ 1: Đơn hàng từ Nghệ An đến Hà Nội

1. **Tạo đơn tại Kho Nghệ An**:
   - Người gửi: Nghệ An
   - Người nhận: Hà Nội
   - Chọn `to_warehouse_id` = Kho Hà Nội

2. **Tài xế lấy hàng** → Nhập Kho Nghệ An

3. **Kho Nghệ An xuất đi Kho Hà Nội**:
   - Chọn tài xế vận chuyển tỉnh
   - Status: `in_transit`

4. **Kho Hà Nội nhận hàng**:
   - Status: `in_warehouse`
   - `warehouse_id` = Kho Hà Nội

5. **Kho Hà Nội xuất cho shipper** → Phân công shipper → Giao hàng

### Ví dụ 2: Đơn hàng giao trực tiếp (không chuyển kho)

1. **Tạo đơn tại Kho Nghệ An**:
   - Người gửi: Nghệ An
   - Người nhận: Nghệ An (cùng tỉnh)
   - Không chọn `to_warehouse_id` (NULL)

2. **Tài xế lấy hàng** → Nhập Kho Nghệ An

3. **Kho Nghệ An xuất cho shipper** → Phân công shipper → Giao hàng

---

## KẾT LUẬN

Luồng nghiệp vụ giữa các kho được thiết kế để:
- ✅ Tối ưu hóa quy trình vận chuyển
- ✅ Giảm chi phí và thời gian giao hàng
- ✅ Theo dõi đầy đủ lịch sử di chuyển đơn hàng
- ✅ Quản lý rõ ràng kho nguồn và kho đích
- ✅ Phân biệt rõ tài xế vận chuyển tỉnh và shipper
- ✅ Hỗ trợ nhập/xuất hàng loạt
- ✅ Tự động hóa nhiều bước trong quy trình
