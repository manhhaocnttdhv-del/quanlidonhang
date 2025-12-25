# DANH SÁCH CHỨC NĂNG HỆ THỐNG QUẢN LÝ VẬN CHUYỂN

## 1. XÁC THỰC VÀ PHÂN QUYỀN

### 1.1. Đăng nhập/Đăng xuất
- Đăng nhập vào hệ thống
- Đăng xuất khỏi hệ thống
- Quản lý phiên đăng nhập

### 1.2. Phân quyền người dùng
- **Super Admin**: Quản lý toàn bộ hệ thống
- **Admin**: Quản lý chung
- **Warehouse Admin**: Quản lý kho cụ thể
- **Dispatcher**: Điều phối đơn hàng
- **Warehouse Staff**: Nhân viên kho
- **Driver**: Tài xế
- **Staff**: Nhân viên

---

## 2. DASHBOARD (BẢNG ĐIỀU KHIỂN)

### 2.1. Thống kê tổng quan
- Tổng số đơn hàng
- Số đơn đã giao thành công
- Số đơn đang xử lý
- Doanh thu trong ngày
- Đơn hàng gần đây (10 đơn mới nhất)

### 2.2. Lọc theo kho
- Warehouse Admin chỉ xem thống kê của kho mình
- Super Admin/Admin xem tất cả

---

## 3. QUẢN LÝ KHÁCH HÀNG

### 3.1. Danh sách khách hàng
- Xem danh sách khách hàng (có phân trang)
- Tìm kiếm theo tên, số điện thoại, mã khách hàng
- Lọc theo trạng thái (active/inactive)
- Warehouse Admin chỉ xem khách hàng của kho mình

### 3.2. Tạo khách hàng mới
- Nhập thông tin khách hàng:
  - Tên, số điện thoại, email
  - Địa chỉ (tỉnh, quận/huyện, phường/xã)
  - Mã số thuế (nếu có)
  - Ghi chú
- Tự động tạo mã khách hàng (format: KH{8 ký tự ngẫu nhiên})
- Warehouse Admin tự động gán kho và tỉnh

### 3.3. Xem chi tiết khách hàng
- Thông tin khách hàng
- Danh sách đơn hàng của khách hàng
- Lịch sử khiếu nại

### 3.4. Cập nhật khách hàng
- Sửa thông tin khách hàng
- Kích hoạt/Vô hiệu hóa khách hàng
- Warehouse Admin không thể đổi kho

### 3.5. Xóa khách hàng
- Xóa khách hàng (nếu không có đơn hàng liên quan)

---

## 4. QUẢN LÝ ĐƠN HÀNG

### 4.1. Danh sách đơn hàng
- Xem danh sách đơn hàng (có phân trang)
- Lọc theo:
  - Trạng thái đơn hàng
  - Mã vận đơn
  - Khách hàng
  - Khoảng thời gian
- Warehouse Admin chỉ xem đơn hàng liên quan đến kho mình
- Sắp xếp theo thời gian tạo (mới nhất trước)

### 4.2. Tạo đơn hàng mới
- Nhập thông tin người gửi:
  - Tên, số điện thoại, địa chỉ
  - Tỉnh, quận/huyện, phường/xã
- Nhập thông tin người nhận:
  - Tên, số điện thoại, địa chỉ
  - Tỉnh, quận/huyện, phường/xã
- Thông tin hàng hóa:
  - Loại hàng
  - Trọng lượng (bắt buộc)
  - Kích thước (dài, rộng, cao)
  - Hàng dễ vỡ (checkbox)
- Thông tin vận chuyển:
  - Số tiền COD (nếu có)
  - Loại dịch vụ (express, standard, economy)
  - Phương thức nhận hàng:
    - **driver**: Tài xế đến lấy (status: pending)
    - **warehouse**: Đưa đến kho (status: in_warehouse)
  - Chọn kho đích (to_warehouse_id)
- Tự động:
  - Tạo mã vận đơn (format: VD{YYYYMMDD}{6 ký tự ngẫu nhiên})
  - Tính phí vận chuyển dựa trên:
    - Tỉnh gửi/nhận
    - Quận/huyện (nếu có)
    - Trọng lượng
    - Loại dịch vụ
    - Số tiền COD
  - Xác định kho tạo đơn (warehouse_id)
  - Tạo trạng thái ban đầu

### 4.3. Xem chi tiết đơn hàng
- Thông tin đơn hàng đầy đủ
- Lịch sử trạng thái (timeline)
- Giao dịch kho (warehouse transactions)
- Khiếu nại (nếu có)
- Thông tin tài xế lấy hàng/giao hàng
- Thông tin tuyến vận chuyển

### 4.4. Sửa đơn hàng
- Cập nhật thông tin đơn hàng
- Tính lại phí vận chuyển nếu có thay đổi
- Chỉ cho phép sửa khi đơn ở trạng thái pending

### 4.5. Xóa đơn hàng
- Xóa đơn hàng (chỉ khi status = pending)
- Xóa các bản ghi liên quan (statuses, warehouse transactions)

### 4.6. Cập nhật trạng thái đơn hàng
- Thay đổi trạng thái đơn hàng
- Thêm ghi chú
- Cập nhật vị trí (location)
- Gán kho (warehouse_id)
- Gán tài xế (driver_id)

### 4.7. Các trạng thái đơn hàng
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

---

## 5. ĐIỀU PHỐI NHẬN HÀNG (DISPATCH)

### 5.1. Danh sách đơn chờ phân công
- Xem đơn hàng chờ phân công tài xế lấy hàng (status: pending)
- Warehouse Admin chỉ xem đơn của kho mình

### 5.2. Danh sách đơn đã phân công
- Xem đơn hàng đã phân công tài xế (status: pickup_pending, picking_up, picked_up)
- Sắp xếp theo mức độ ưu tiên

### 5.3. Phân công tài xế lấy hàng
- Chọn một hoặc nhiều đơn hàng
- Chọn tài xế
- Đặt lịch lấy hàng (pickup_scheduled_at)
- Tự động cập nhật status: pending → pickup_pending
- Warehouse Admin chỉ phân công tài xế của kho mình

### 5.4. Tự động phân công tài xế
- Hệ thống tự động phân công tài xế ngẫu nhiên
- Chọn nhiều đơn hàng cùng lúc
- Phân công cho tài xế khả dụng

### 5.5. Cập nhật trạng thái lấy hàng
- **picking_up**: Tài xế đang đi lấy hàng
- **picked_up**: Tài xế đã lấy hàng
  - Tự động nhập kho của tài xế
  - Tạo warehouse transaction (type: in)
  - Cập nhật status: picked_up → in_warehouse
  - Ghi chú tự động

### 5.6. Danh sách tài xế khả dụng
- Xem tài xế đang hoạt động
- Lọc theo khu vực (nếu có)
- Warehouse Admin chỉ xem tài xế của kho mình

---

## 6. QUẢN LÝ KHO

### 6.1. Danh sách kho
- Xem tất cả kho đang hoạt động
- Super Admin/Admin xem tất cả
- Warehouse Admin tự động redirect đến kho của mình

### 6.2. Tạo kho mới
- Nhập thông tin kho:
  - Mã kho (unique)
  - Tên kho
  - Địa chỉ
  - Tỉnh/thành phố (từ database provinces)
  - Mã tỉnh (province_code)
  - Phường/xã (ward)
  - Mã phường (ward_code)
  - Số điện thoại
  - Tên quản lý
  - Ghi chú
- Chọn admin kho (gán user làm warehouse_admin)
- Chỉ Super Admin/Admin mới được tạo kho

### 6.3. Xem chi tiết kho
- Thông tin kho
- **Tồn kho hiện tại**:
  - Tất cả đơn hàng trong kho (status: in_warehouse)
  - Đơn hàng từ tài xế lấy về
  - Đơn hàng nhận từ kho khác
- **Đơn hàng đang đến**:
  - Đơn hàng đang vận chuyển đến kho này (status: in_transit)
  - Đơn hàng có to_warehouse_id = kho này
- **Đơn hàng đang giao hàng**:
  - Đơn hàng đang giao trong khu vực kho này (status: out_for_delivery)
- **Thống kê**:
  - Tổng số đơn trong kho
  - Nhập kho hôm nay
  - Xuất kho hôm nay
  - Hàng vừa nhận từ tài xế hôm nay
  - Hàng vừa nhận từ kho khác hôm nay
  - Hàng vừa xuất hôm nay
  - Lịch sử xuất kho (7 ngày gần nhất)
- **Danh sách tài xế** của kho
- **Tuyến vận chuyển** từ kho này

### 6.4. Sửa thông tin kho
- Cập nhật thông tin kho
- Warehouse Admin chỉ sửa được kho của mình
- Super Admin/Admin sửa được tất cả

### 6.5. Nhập kho
- Nhận đơn hàng vào kho
- Chọn đơn hàng
- Chọn kho gửi (from_warehouse_id) - nếu nhận từ kho khác
- Thêm số tham chiếu (reference_number)
- Thêm ghi chú
- Tự động:
  - Cập nhật status: in_transit → in_warehouse
  - Xóa to_warehouse_id
  - Tạo warehouse transaction (type: in)
  - Tạo OrderStatus

### 6.6. Nhập kho hàng loạt
- Chọn nhiều đơn hàng cùng lúc
- Nhập kho hàng loạt
- Tự động xử lý từng đơn

### 6.7. Xuất kho (cho shipper giao hàng)
- Xuất đơn hàng để phân công tài xế giao
- Chọn đơn hàng
- Chọn tuyến (route) - tự động tìm nếu chưa chọn
- Thêm số tham chiếu
- Thêm ghi chú
- Tạo warehouse transaction (type: out)
- Đơn hàng vẫn ở trạng thái in_warehouse, chờ phân công tài xế

### 6.8. Xuất kho hàng loạt
- Chọn nhiều đơn hàng cùng lúc
- Xuất kho hàng loạt
- Tự động tìm tuyến cho từng đơn

### 6.9. Vận chuyển đến kho khác
- Chọn một hoặc nhiều đơn hàng
- Chọn kho đích (to_warehouse_id)
- Chọn tài xế vận chuyển tỉnh (intercity_driver_id) - tùy chọn
- Thêm số tham chiếu
- Thêm ghi chú
- Tự động:
  - Cập nhật status: in_warehouse → in_transit
  - Set to_warehouse_id
  - Tạo OrderStatus
  - Tạo warehouse transaction (type: out)
  - Gán tài xế vận chuyển tỉnh vào delivery_driver_id

### 6.10. Lọc theo tỉnh
- Lọc đơn hàng trong kho theo tỉnh người nhận
- Áp dụng cho: tồn kho, đơn từ tài xế, đơn từ kho khác

### 6.11. Xem lịch sử giao dịch
- Xem tất cả giao dịch nhập/xuất kho
- Lọc theo:
  - Loại giao dịch (in/out)
  - Khoảng thời gian
- Xem chi tiết từng giao dịch

---

## 7. GIAO HÀNG (DELIVERY)

### 7.1. Đơn hàng đã xuất kho (để chuyển đến kho khác)
- Xem đơn hàng đã xuất từ kho này để chuyển đến kho khác
- Đơn hàng có to_warehouse_id khác với warehouse_id hiện tại
- Warehouse Admin chỉ xem đơn của kho mình
- Lọc theo tỉnh người nhận

### 7.2. Đơn hàng đang đến kho này
- Xem đơn hàng đang vận chuyển đến kho này
- Đơn hàng có to_warehouse_id = kho này HOẶC receiver_province = tỉnh của kho
- Chưa được nhận vào kho (chưa có transaction 'in' tại kho này)
- Warehouse Admin chỉ xem đơn đến kho mình

### 7.3. Đơn hàng sẵn sàng giao
- Xem đơn hàng trong kho (status: in_warehouse)
- Đã xuất kho (có transaction 'out')
- Chưa phân công tài xế giao hàng
- Lọc theo tỉnh người nhận

### 7.4. Phân công tài xế giao hàng
- Chọn một hoặc nhiều đơn hàng
- Chọn tài xế shipper
- Đặt lịch giao hàng (delivery_scheduled_at)
- Tự động:
  - Cập nhật status: in_warehouse → out_for_delivery
  - Gán delivery_driver_id
  - Tạo OrderStatus

### 7.5. Phân công hàng loạt
- Chọn nhiều đơn hàng cùng lúc
- Phân công cho một tài xế
- Tự động xử lý từng đơn

### 7.6. Cập nhật trạng thái giao hàng
- **delivered**: Đã giao thành công
  - Cập nhật delivered_at
  - Nhập số tiền COD đã thu (cod_collected)
  - Tạo OrderStatus
- **failed**: Giao hàng thất bại
  - Nhập lý do thất bại
  - Tạo OrderStatus

### 7.7. Danh sách đơn đã giao
- Xem đơn hàng đã giao thành công
- Lọc theo:
  - Khoảng thời gian
  - Tài xế
  - Tỉnh
- Warehouse Admin chỉ xem đơn của kho mình

### 7.8. Thống kê tài xế
- Số đơn đã giao
- Số đơn thất bại
- Tỷ lệ thành công

---

## 8. TRA CỨU ĐƠN HÀNG (TRACKING)

### 8.1. Tra cứu công khai
- Nhập mã vận đơn
- Xem thông tin đơn hàng
- Xem lịch sử trạng thái (timeline)
- Không cần đăng nhập

### 8.2. Tra cứu chi tiết (cần đăng nhập)
- Tra cứu với quyền truy cập đầy đủ
- Xem tất cả thông tin đơn hàng
- Xem lịch sử chi tiết

---

## 9. QUẢN LÝ PHÍ VẬN CHUYỂN

### 9.1. Danh sách bảng cước
- Xem tất cả bảng cước vận chuyển
- Lọc theo:
  - Tỉnh gửi/nhận
  - Loại dịch vụ
  - Trạng thái (active/inactive)

### 9.2. Tính phí vận chuyển
- Nhập thông tin:
  - Tỉnh/quận gửi
  - Tỉnh/quận nhận
  - Trọng lượng
  - Loại dịch vụ
  - Số tiền COD
- Hệ thống tự động tính phí dựa trên:
  - Priority 1: Khớp chính xác (tỉnh + quận)
  - Priority 2: Khớp theo tỉnh
  - Priority 3: Khớp theo vùng (Bắc/Trung/Nam)
  - Priority 4: Phí mặc định
- Công thức: Phí cơ bản + (Trọng lượng - Trọng lượng tối thiểu) × Phí/kg + COD × % phí COD

### 9.3. Tạo bảng cước mới
- Nhập thông tin bảng cước:
  - Tỉnh/quận gửi
  - Tỉnh/quận nhận
  - Loại dịch vụ (express, standard, economy)
  - Phí cơ bản
  - Phí/kg
  - Trọng lượng tối thiểu
  - Phí COD (%)
  - Trọng lượng tối đa
  - Trạng thái (active/inactive)

---

## 10. BÁO CÁO

### 10.1. Báo cáo tổng quan
- Thống kê theo ngày:
  - Tổng số đơn hàng
  - Số đơn đã giao
  - Số đơn thất bại
  - Doanh thu (COD + Phí vận chuyển)
- Thống kê theo khoảng thời gian:
  - Nhóm theo ngày
  - Tổng số đơn, đơn đã giao, đơn thất bại
  - Doanh thu, COD, Phí vận chuyển
- Warehouse Admin chỉ xem báo cáo của kho mình

### 10.2. Báo cáo theo ngày
- Thống kê chi tiết một ngày cụ thể
- Tổng số đơn, đơn đã giao, đơn thất bại, đơn hoàn
- Doanh thu, COD, COD đã thu

### 10.3. Báo cáo theo tháng
- Thống kê chi tiết một tháng cụ thể
- Tổng số đơn, đơn đã giao, đơn thất bại
- Doanh thu, COD

### 10.4. Báo cáo hiệu suất tài xế
- Thống kê theo khoảng thời gian
- Số đơn đã giao, số đơn thất bại
- Tỷ lệ thành công

### 10.5. Báo cáo kho
- Thống kê nhập/xuất kho
- Tồn kho hiện tại
- Số giao dịch nhập/xuất

### 10.6. Báo cáo doanh thu
- Doanh thu theo ngày
- Phí vận chuyển, COD
- Số đơn hàng

### 10.7. Tổng quan tất cả kho (Super Admin)
- Thống kê tổng hợp tất cả kho
- Tồn kho hiện tại
- Đơn hàng đang đến
- Thống kê trong khoảng thời gian:
  - Tổng số đơn, đơn đã giao
  - Đơn trong kho, đơn đang vận chuyển
  - Doanh thu, COD
- Giao dịch nhập/xuất
- Đơn nhận từ kho khác, đơn xuất đi kho khác
- Số lượng tài xế (tổng, shipper, vận chuyển tỉnh)
- Danh sách admin kho

---

## 11. BẢNG KÊ COD (COD RECONCILIATION)

### 11.1. Danh sách bảng kê
- Xem tất cả bảng kê COD
- Lọc theo:
  - Khách hàng
  - Trạng thái (pending, partial, paid)
- Sắp xếp theo thời gian tạo (mới nhất trước)

### 11.2. Tạo bảng kê mới
- Chọn khách hàng (tùy chọn)
- Chọn khoảng thời gian (from_date, to_date)
- Chọn các đơn hàng đã giao (status: delivered)
- Tự động tính:
  - Tổng COD
  - Tổng phí vận chuyển
  - Tổng tiền (COD + Phí vận chuyển)
- Tự động tạo số bảng kê (format: BK{YYYYMMDD}{6 ký tự ngẫu nhiên})
- Trạng thái ban đầu: pending

### 11.3. Xem chi tiết bảng kê
- Thông tin bảng kê:
  - Số bảng kê
  - Khách hàng
  - Khoảng thời gian
  - Tổng COD, Phí vận chuyển, Tổng tiền
  - Số tiền đã thanh toán
  - Số tiền còn lại
  - Trạng thái
- Danh sách đơn hàng trong bảng kê:
  - Mã vận đơn
  - COD, Phí vận chuyển
  - Ngày giao hàng

### 11.4. Cập nhật thanh toán
- Nhập số tiền đã thanh toán
- Tự động tính số tiền còn lại
- Tự động cập nhật trạng thái:
  - **partial**: Đã thanh toán một phần
  - **paid**: Đã thanh toán đủ

---

## 12. QUẢN LÝ TÀI XẾ

### 12.1. Danh sách tài xế
- Xem tất cả tài xế
- Lọc theo:
  - Kho
  - Loại tài xế (shipper, intercity_driver)
  - Trạng thái (active/inactive)
- Warehouse Admin chỉ xem tài xế của kho mình

### 12.2. Tạo tài xế mới
- Nhập thông tin:
  - Tên, số điện thoại
  - Loại tài xế:
    - **shipper**: Tài xế giao hàng trong tỉnh
    - **intercity_driver**: Tài xế vận chuyển tỉnh
  - Kho (warehouse_id)
  - Khu vực (area)
  - Ghi chú
- Tự động tạo mã tài xế (format: TX{8 ký tự ngẫu nhiên})

### 12.3. Xem chi tiết tài xế
- Thông tin tài xế
- Danh sách đơn hàng đã lấy/giao
- Thống kê hiệu suất

### 12.4. Cập nhật tài xế
- Sửa thông tin tài xế
- Kích hoạt/Vô hiệu hóa tài xế

---

## 13. QUẢN LÝ TUYẾN VẬN CHUYỂN

### 13.1. Danh sách tuyến
- Xem tất cả tuyến vận chuyển
- Lọc theo:
  - Tỉnh gửi/nhận
  - Trạng thái (active/inactive)

### 13.2. Tạo tuyến mới
- Nhập thông tin:
  - Tỉnh gửi (from_province)
  - Tỉnh nhận (to_province)
  - Tên tuyến
  - Khoảng cách (km)
  - Thời gian vận chuyển ước tính
  - Ghi chú
  - Trạng thái (active/inactive)

### 13.3. Xem chi tiết tuyến
- Thông tin tuyến
- Danh sách đơn hàng đã sử dụng tuyến này

---

## 14. QUẢN LÝ NGƯỜI DÙNG

### 14.1. Danh sách người dùng
- Xem tất cả người dùng
- Lọc theo:
  - Vai trò (role)
  - Kho (warehouse_id)
  - Trạng thái (active/inactive)
- Chỉ Super Admin mới xem được

### 14.2. Tạo người dùng mới
- Nhập thông tin:
  - Tên, email, số điện thoại
  - Mật khẩu
  - Vai trò (role)
  - Kho (warehouse_id) - nếu là warehouse_admin
  - Trạng thái (active/inactive)

### 14.3. Xem chi tiết người dùng
- Thông tin người dùng
- Lịch sử hoạt động

### 14.4. Cập nhật người dùng
- Sửa thông tin người dùng
- Đổi mật khẩu
- Thay đổi vai trò
- Kích hoạt/Vô hiệu hóa

### 14.5. Xóa người dùng
- Xóa người dùng (cẩn thận với dữ liệu liên quan)

---

## 15. API HỖ TRỢ

### 15.1. API Lấy danh sách tỉnh/thành phố
- GET `/api/provinces`
- Trả về danh sách tỉnh/thành phố từ database

### 15.2. API Lấy danh sách phường/xã
- GET `/api/wards?province_code={code}`
- Trả về danh sách phường/xã theo mã tỉnh

### 15.3. API Lấy danh sách kho
- GET `/api/warehouses?province={tên tỉnh}`
- Trả về danh sách kho theo tỉnh
- Tự động normalize tên tỉnh (bỏ "Thành phố", "Tỉnh")

---

## 16. CÁC TÍNH NĂNG ĐẶC BIỆT

### 16.1. Tự động tạo mã
- **Mã vận đơn**: VD{YYYYMMDD}{6 ký tự ngẫu nhiên}
- **Mã khách hàng**: KH{8 ký tự ngẫu nhiên}
- **Mã tài xế**: TX{8 ký tự ngẫu nhiên}
- **Số bảng kê**: BK{YYYYMMDD}{6 ký tự ngẫu nhiên}

### 16.2. Tính phí vận chuyển thông minh
- Ưu tiên khớp chính xác (tỉnh + quận)
- Fallback theo tỉnh
- Fallback theo vùng (Bắc/Trung/Nam)
- Fallback phí mặc định
- Tính theo: Phí cơ bản + Phí theo trọng lượng + Phí COD

### 16.3. Quản lý kho đa cấp
- Kho nguồn (warehouse_id): Kho tạo đơn hàng
- Kho đích (to_warehouse_id): Kho sẽ nhận đơn hàng
- Tự động nhập kho khi tài xế lấy hàng về
- Vận chuyển giữa các kho

### 16.4. Lịch sử trạng thái (Timeline)
- Ghi lại mọi thay đổi trạng thái đơn hàng
- Lưu thông tin: người cập nhật, thời gian, ghi chú, vị trí, kho, tài xế

### 16.5. Giao dịch kho (Warehouse Transactions)
- Ghi lại mọi giao dịch nhập/xuất kho
- Lưu thông tin: kho, đơn hàng, loại (in/out), tuyến, số tham chiếu, ghi chú, người tạo

### 16.6. Phân quyền theo kho
- Warehouse Admin chỉ xem/quản lý dữ liệu của kho mình
- Tự động filter theo warehouse_id
- Áp dụng cho: đơn hàng, khách hàng, tài xế, báo cáo

---

## 17. QUY TRÌNH NGHIỆP VỤ

### 17.1. Quy trình tạo đơn hàng
1. Nhân viên tạo đơn hàng
2. Hệ thống tự động:
   - Tạo mã vận đơn
   - Tính phí vận chuyển
   - Xác định kho tạo đơn
   - Tạo trạng thái ban đầu
3. Nếu phương thức nhận = "warehouse": Đơn tự động vào kho (status: in_warehouse)
4. Nếu phương thức nhận = "driver": Đơn chờ phân công tài xế (status: pending)

### 17.2. Quy trình lấy hàng
1. Điều phối xem đơn chờ lấy (status: pending)
2. Phân công tài xế lấy hàng (status: pickup_pending)
3. Tài xế cập nhật: Đang đi lấy (status: picking_up)
4. Tài xế cập nhật: Đã lấy hàng (status: picked_up)
5. Hệ thống tự động:
   - Nhập kho của tài xế (status: in_warehouse)
   - Tạo warehouse transaction (type: in)

### 17.3. Quy trình vận chuyển giữa kho
1. Kho nguồn xuất hàng đi kho khác
2. Chọn đơn hàng, kho đích, tài xế vận chuyển tỉnh
3. Hệ thống tự động:
   - Cập nhật status: in_warehouse → in_transit
   - Set to_warehouse_id
   - Tạo warehouse transaction (type: out)
   - Tạo OrderStatus
4. Kho đích nhận hàng
5. Hệ thống tự động:
   - Cập nhật status: in_transit → in_warehouse
   - Xóa to_warehouse_id
   - Tạo warehouse transaction (type: in)
   - Tạo OrderStatus

### 17.4. Quy trình giao hàng
1. Kho xuất hàng cho shipper (tạo transaction 'out')
2. Phân công tài xế shipper giao hàng
3. Hệ thống tự động:
   - Cập nhật status: in_warehouse → out_for_delivery
   - Gán delivery_driver_id
   - Tạo OrderStatus
4. Tài xế giao hàng:
   - **Thành công**: Cập nhật status: delivered, nhập COD đã thu
   - **Thất bại**: Cập nhật status: failed, nhập lý do

### 17.5. Quy trình đối soát COD
1. Tạo bảng kê COD
2. Chọn khách hàng, khoảng thời gian, đơn hàng đã giao
3. Hệ thống tự động tính:
   - Tổng COD
   - Tổng phí vận chuyển
   - Tổng tiền
4. Cập nhật thanh toán
5. Hệ thống tự động cập nhật trạng thái (pending → partial → paid)

---

## TỔNG KẾT

Hệ thống quản lý vận chuyển này bao gồm **17 nhóm chức năng chính** với hơn **100 tính năng chi tiết**, hỗ trợ toàn bộ quy trình từ tiếp nhận đơn hàng đến giao hàng và đối soát COD, với khả năng quản lý đa kho, phân quyền chi tiết, và tự động hóa nhiều quy trình nghiệp vụ.
