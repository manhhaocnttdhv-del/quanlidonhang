# HƯỚNG DẪN HIỂU CODE - HỆ THỐNG QUẢN LÝ VẬN CHUYỂN

## 📋 MỤC LỤC

1. [Tổng quan về công nghệ](#1-tổng-quan-về-công-nghệ)
2. [Cấu trúc thư mục](#2-cấu-trúc-thư-mục)
3. [Kiến trúc MVC](#3-kiến-trúc-mvc)
4. [Cách đọc Models](#4-cách-đọc-models)
5. [Cách đọc Controllers](#5-cách-đọc-controllers)
6. [Cách đọc Routes](#6-cách-đọc-routes)
7. [Database và Migrations](#7-database-và-migrations)
8. [Eloquent Relationships](#8-eloquent-relationships)
9. [Các Pattern được sử dụng](#9-các-pattern-được-sử-dụng)
10. [Cách Debug và Trace Code](#10-cách-debug-và-trace-code)
11. [Các điểm quan trọng](#11-các-điểm-quan-trọng)

---

## 1. TỔNG QUAN VỀ CÔNG NGHỆ

### Framework: Laravel 10
- **PHP Version**: ^8.1
- **Framework**: Laravel 10.10
- **Authentication**: Laravel Sanctum
- **Database**: MySQL/MariaDB (qua Eloquent ORM)

### Các thư viện chính:
- `laravel/framework`: Core framework
- `laravel/sanctum`: API authentication
- `laravel/tinker`: REPL cho Laravel

---

## 2. CẤU TRÚC THƯ MỤC

```
quanlidonhang/
├── app/                          # Code chính của ứng dụng
│   ├── Console/                  # Artisan commands
│   ├── Exceptions/                # Exception handlers
│   ├── Http/                      # HTTP layer
│   │   ├── Controllers/           # Controllers (MVC)
│   │   │   ├── Admin/             # Admin controllers
│   │   │   └── Auth/               # Authentication controllers
│   │   ├── Middleware/            # Middleware (xử lý request/response)
│   │   └── Kernel.php             # HTTP Kernel
│   ├── Models/                    # Eloquent Models (Database)
│   └── Providers/                 # Service Providers
├── bootstrap/                     # Bootstrap files
├── config/                        # Configuration files
├── database/                      # Database
│   ├── migrations/                # Database migrations
│   └── seeders/                   # Database seeders
├── public/                        # Public assets (entry point)
├── resources/                      # Views, CSS, JS
│   └── views/                     # Blade templates
├── routes/                        # Route definitions
│   ├── web.php                    # Web routes
│   └── api.php                    # API routes
└── storage/                       # Logs, cache, files
```

---

## 3. KIẾN TRÚC MVC

Hệ thống sử dụng **MVC (Model-View-Controller)** pattern:

```
Request → Route → Controller → Model → Database
                ↓
              View ← Response
```

### Flow xử lý request:

1. **Request** đến từ browser/API
2. **Route** (`routes/web.php`) định tuyến request đến Controller
3. **Controller** xử lý logic nghiệp vụ
4. **Model** tương tác với Database
5. **View** (Blade template) render HTML response

### Ví dụ:

```php
// routes/web.php
Route::get('/orders', [OrderController::class, 'index']);

// app/Http/Controllers/OrderController.php
public function index() {
    $orders = Order::all();  // Model
    return view('admin.orders.index', compact('orders'));  // View
}
```

---

## 4. CÁCH ĐỌC MODELS

### 4.1. Cấu trúc Model cơ bản

Models nằm trong `app/Models/`, kế thừa từ `Illuminate\Database\Eloquent\Model`.

**Ví dụ: Order Model**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;  // Hỗ trợ soft delete
    
    // Các trường có thể mass assign
    protected $fillable = [
        'tracking_number',
        'customer_id',
        'sender_name',
        // ...
    ];
    
    // Type casting (tự động convert kiểu dữ liệu)
    protected $casts = [
        'weight' => 'decimal:2',
        'is_fragile' => 'boolean',
        'picked_up_at' => 'datetime',
    ];
    
    // Relationships (quan hệ với Models khác)
    public function customer() {
        return $this->belongsTo(Customer::class);
    }
    
    public function statuses() {
        return $this->hasMany(OrderStatus::class);
    }
}
```

### 4.2. Các thành phần quan trọng:

#### a) `$fillable` - Mass Assignment Protection
```php
protected $fillable = ['name', 'email'];
// Chỉ các trường này mới có thể được gán qua create() hoặc update()
```

#### b) `$casts` - Type Casting
```php
protected $casts = [
    'is_active' => 'boolean',      // Tự động convert 0/1 → false/true
    'price' => 'decimal:2',        // Tự động format số thập phân
    'created_at' => 'datetime',   // Tự động convert string → Carbon instance
];
```

#### c) Relationships - Quan hệ giữa các Models

**belongsTo** - Quan hệ "nhiều thuộc về một":
```php
// Order belongsTo Customer (một đơn hàng thuộc về một khách hàng)
public function customer() {
    return $this->belongsTo(Customer::class);
}

// Sử dụng:
$order->customer;  // Lấy Customer của Order
$order->customer->name;  // Lấy tên khách hàng
```

**hasMany** - Quan hệ "một có nhiều":
```php
// Order hasMany OrderStatus (một đơn hàng có nhiều trạng thái)
public function statuses() {
    return $this->hasMany(OrderStatus::class);
}

// Sử dụng:
$order->statuses;  // Lấy tất cả OrderStatus của Order
$order->statuses->count();  // Đếm số trạng thái
```

**belongsToMany** - Quan hệ nhiều-nhiều:
```php
// Order belongsToMany CodReconciliation (qua bảng trung gian)
public function codReconciliations() {
    return $this->belongsToMany(CodReconciliation::class, 'cod_reconciliation_orders')
        ->withPivot('cod_amount', 'shipping_fee');
}

// Sử dụng:
$order->codReconciliations;  // Lấy tất cả CodReconciliation
```

### 4.3. Các Model chính trong hệ thống:

| Model | Mô tả | Quan hệ chính |
|-------|-------|---------------|
| `Order` | Đơn hàng | belongsTo: Customer, Warehouse, Route<br>hasMany: OrderStatus, WarehouseTransaction |
| `Customer` | Khách hàng | hasMany: Order |
| `Warehouse` | Kho | hasMany: Order, Driver, WarehouseTransaction |
| `Driver` | Tài xế | belongsTo: Warehouse<br>hasMany: Order (pickup/delivery) |
| `OrderStatus` | Trạng thái đơn hàng | belongsTo: Order, Warehouse, Driver |
| `WarehouseTransaction` | Giao dịch kho | belongsTo: Order, Warehouse |
| `User` | Người dùng | belongsTo: Warehouse |

### 4.4. Các method đặc biệt trong Models:

**Static methods** - Có thể gọi trực tiếp từ class:
```php
// Warehouse.php
public static function getDefaultWarehouse() {
    return static::where('is_active', true)
        ->where('province', 'Nghệ An')
        ->first();
}

// Sử dụng:
$warehouse = Warehouse::getDefaultWarehouse();
```

**Instance methods** - Gọi từ object:
```php
// User.php
public function isWarehouseAdmin(): bool {
    return $this->role === 'warehouse_admin';
}

// Sử dụng:
if ($user->isWarehouseAdmin()) {
    // ...
}
```

---

## 5. CÁCH ĐỌC CONTROLLERS

### 5.1. Cấu trúc Controller cơ bản

Controllers nằm trong `app/Http/Controllers/`, xử lý logic nghiệp vụ.

**Ví dụ: OrderController**

```php
<?php
namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // GET /orders - Hiển thị danh sách
    public function index(Request $request) {
        $orders = Order::with(['customer', 'warehouse'])->get();
        return view('admin.orders.index', compact('orders'));
    }
    
    // GET /orders/create - Form tạo mới
    public function create() {
        return view('admin.orders.create');
    }
    
    // POST /orders - Lưu đơn hàng mới
    public function store(Request $request) {
        $validated = $request->validate([...]);
        $order = Order::create($validated);
        return redirect()->route('admin.orders.show', $order->id);
    }
    
    // GET /orders/{id} - Hiển thị chi tiết
    public function show($id) {
        $order = Order::findOrFail($id);
        return view('admin.orders.show', compact('order'));
    }
    
    // PUT /orders/{id} - Cập nhật
    public function update(Request $request, $id) {
        $order = Order::findOrFail($id);
        $order->update($request->validated());
        return redirect()->back();
    }
    
    // DELETE /orders/{id} - Xóa
    public function destroy($id) {
        $order = Order::findOrFail($id);
        $order->delete();
        return redirect()->route('admin.orders.index');
    }
}
```

### 5.2. Các method CRUD chuẩn:

| Method | Route | Mô tả |
|--------|-------|-------|
| `index()` | GET `/resource` | Danh sách |
| `create()` | GET `/resource/create` | Form tạo mới |
| `store()` | POST `/resource` | Lưu mới |
| `show($id)` | GET `/resource/{id}` | Chi tiết |
| `edit($id)` | GET `/resource/{id}/edit` | Form sửa |
| `update($id)` | PUT `/resource/{id}` | Cập nhật |
| `destroy($id)` | DELETE `/resource/{id}` | Xóa |

### 5.3. Request và Validation:

**Lấy dữ liệu từ Request:**
```php
public function store(Request $request) {
    // Lấy tất cả
    $data = $request->all();
    
    // Lấy một trường
    $name = $request->input('name');
    $name = $request->name;  // Tương tự
    
    // Lấy với giá trị mặc định
    $status = $request->get('status', 'pending');
    
    // Kiểm tra có trường không
    if ($request->has('email')) {
        // ...
    }
}
```

**Validation:**
```php
public function store(Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'age' => 'nullable|integer|min:18',
    ]);
    
    // Nếu validation fail, tự động redirect back với errors
    // Nếu pass, $validated chứa dữ liệu đã validate
    
    User::create($validated);
}
```

### 5.4. Query Builder và Eloquent:

**Eloquent (ORM) - Dễ đọc hơn:**
```php
// Lấy tất cả
$orders = Order::all();

// Lấy với điều kiện
$orders = Order::where('status', 'pending')->get();

// Lấy một record
$order = Order::find($id);
$order = Order::findOrFail($id);  // Throw 404 nếu không tìm thấy

// Lấy với relationships (eager loading)
$orders = Order::with(['customer', 'warehouse'])->get();

// Tạo mới
$order = Order::create([
    'tracking_number' => 'VD123',
    'status' => 'pending',
]);

// Cập nhật
$order->update(['status' => 'delivered']);

// Xóa
$order->delete();
```

**Query Builder - Linh hoạt hơn:**
```php
// Join tables
$orders = DB::table('orders')
    ->join('customers', 'orders.customer_id', '=', 'customers.id')
    ->select('orders.*', 'customers.name as customer_name')
    ->get();

// Aggregations
$total = Order::where('status', 'delivered')->sum('shipping_fee');
$count = Order::where('status', 'pending')->count();
```

### 5.5. Response:

**View (HTML):**
```php
return view('admin.orders.index', [
    'orders' => $orders,
    'title' => 'Danh sách đơn hàng'
]);

// Hoặc dùng compact()
return view('admin.orders.index', compact('orders', 'title'));
```

**JSON (API):**
```php
return response()->json([
    'message' => 'Thành công',
    'data' => $order
], 201);

// Hoặc
return response()->json($order);
```

**Redirect:**
```php
return redirect()->route('admin.orders.index');
return redirect()->back();
return redirect()->back()->with('success', 'Đã lưu thành công');
```

### 5.6. Phân quyền trong Controllers:

**Kiểm tra quyền:**
```php
public function index(Request $request) {
    $user = auth()->user();
    
    // Warehouse admin chỉ xem đơn hàng của kho mình
    if ($user->isWarehouseAdmin() && $user->warehouse_id) {
        $orders = Order::where('warehouse_id', $user->warehouse_id)->get();
    } else {
        $orders = Order::all();
    }
    
    return view('admin.orders.index', compact('orders'));
}
```

**Middleware trong routes:**
```php
// routes/web.php
Route::middleware('auth')->group(function() {
    Route::get('/orders', [OrderController::class, 'index']);
});
```

---

## 6. CÁCH ĐỌC ROUTES

### 6.1. File routes/web.php

**Cấu trúc cơ bản:**
```php
use App\Http\Controllers\OrderController;

// Route đơn giản
Route::get('/orders', [OrderController::class, 'index']);

// Route với parameter
Route::get('/orders/{id}', [OrderController::class, 'show']);

// Route resource (tự động tạo CRUD routes)
Route::resource('orders', OrderController::class);

// Route group với prefix và middleware
Route::prefix('admin')->middleware('auth')->group(function() {
    Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders.index');
});
```

### 6.2. Các loại Route:

| Method | Route | Controller Method | Mô tả |
|--------|-------|-------------------|-------|
| GET | `/orders` | `index()` | Danh sách |
| GET | `/orders/create` | `create()` | Form tạo |
| POST | `/orders` | `store()` | Lưu mới |
| GET | `/orders/{id}` | `show($id)` | Chi tiết |
| GET | `/orders/{id}/edit` | `edit($id)` | Form sửa |
| PUT | `/orders/{id}` | `update($id)` | Cập nhật |
| DELETE | `/orders/{id}` | `destroy($id)` | Xóa |

### 6.3. Route naming:

```php
Route::get('/orders', [OrderController::class, 'index'])
    ->name('admin.orders.index');

// Sử dụng trong code:
return redirect()->route('admin.orders.index');
// Hoặc trong Blade:
<a href="{{ route('admin.orders.index') }}">Danh sách</a>
```

### 6.4. Route parameters:

```php
// Single parameter
Route::get('/orders/{id}', [OrderController::class, 'show']);
// Controller: public function show($id) { ... }

// Multiple parameters
Route::get('/orders/{order}/statuses/{status}', ...);
// Controller: public function show($order, $status) { ... }

// Optional parameter
Route::get('/orders/{id?}', ...);
```

---

## 7. DATABASE VÀ MIGRATIONS

### 7.1. Migrations

Migrations nằm trong `database/migrations/`, định nghĩa cấu trúc database.

**Ví dụ:**
```php
// 2025_12_12_134023_create_orders_table.php
public function up() {
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->string('tracking_number')->unique();
        $table->foreignId('customer_id')->nullable()->constrained();
        $table->string('sender_name');
        $table->string('receiver_name');
        $table->decimal('weight', 8, 2);
        $table->enum('status', ['pending', 'delivered', ...]);
        $table->timestamps();
        $table->softDeletes();
    });
}
```

### 7.2. Chạy migrations:

```bash
php artisan migrate          # Chạy migrations
php artisan migrate:rollback # Rollback migration cuối
php artisan migrate:fresh     # Xóa và tạo lại tất cả
```

### 7.3. Seeders

Seeders nằm trong `database/seeders/`, dùng để tạo dữ liệu mẫu.

```php
// DatabaseSeeder.php
public function run() {
    $this->call([
        UserSeeder::class,
        WarehouseSeeder::class,
        CustomerSeeder::class,
    ]);
}
```

---

## 8. ELOQUENT RELATIONSHIPS

### 8.1. Các loại relationships:

**belongsTo** - "Nhiều thuộc về một":
```php
// Order belongsTo Customer
// orders.customer_id → customers.id

class Order extends Model {
    public function customer() {
        return $this->belongsTo(Customer::class);
    }
}

// Sử dụng:
$order->customer;  // Lấy Customer
$order->customer->name;  // Lấy tên khách hàng
```

**hasMany** - "Một có nhiều":
```php
// Customer hasMany Order
// customers.id → orders.customer_id

class Customer extends Model {
    public function orders() {
        return $this->hasMany(Order::class);
    }
}

// Sử dụng:
$customer->orders;  // Collection of Orders
$customer->orders->count();
```

**belongsToMany** - "Nhiều-nhiều":
```php
// Order belongsToMany CodReconciliation
// Qua bảng trung gian: cod_reconciliation_orders

class Order extends Model {
    public function codReconciliations() {
        return $this->belongsToMany(CodReconciliation::class, 'cod_reconciliation_orders')
            ->withPivot('cod_amount', 'shipping_fee');
    }
}
```

### 8.2. Eager Loading - Tối ưu query:

**N+1 Problem:**
```php
// ❌ BAD: Query nhiều lần
$orders = Order::all();
foreach ($orders as $order) {
    echo $order->customer->name;  // Query mỗi lần lặp
}
```

**Eager Loading:**
```php
// ✅ GOOD: Query một lần
$orders = Order::with('customer')->get();
foreach ($orders as $order) {
    echo $order->customer->name;  // Không query thêm
}
```

**Nested Eager Loading:**
```php
$orders = Order::with(['customer', 'warehouse', 'statuses'])->get();
```

---

## 9. CÁC PATTERN ĐƯỢC SỬ DỤNG

### 9.1. Repository Pattern (Một phần)

Models có các static methods để truy vấn:
```php
// Warehouse.php
public static function getDefaultWarehouse() {
    return static::where('is_active', true)
        ->where('province', 'Nghệ An')
        ->first();
}
```

### 9.2. Service Pattern (Một phần)

Logic nghiệp vụ phức tạp được đặt trong Controllers:
```php
// OrderController.php
private function calculateShippingFee(...) {
    // Logic tính phí phức tạp
}
```

### 9.3. Middleware Pattern

Xử lý request/response trước và sau Controller:
```php
// app/Http/Middleware/Authenticate.php
public function handle($request, Closure $next) {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    return $next($request);
}
```

---

## 10. CÁCH DEBUG VÀ TRACE CODE

### 10.1. Logging:

```php
use Illuminate\Support\Facades\Log;

Log::info('Đơn hàng đã được tạo', ['order_id' => $order->id]);
Log::error('Lỗi khi tạo đơn hàng', ['error' => $e->getMessage()]);
Log::debug('Debug info', $data);
```

**Xem logs:**
```bash
tail -f storage/logs/laravel.log
```

### 10.2. dd() và dump():

```php
// dd() - Dump and Die (dừng execution)
dd($order);

// dump() - Chỉ dump, không dừng
dump($order);
// Code tiếp tục chạy
```

### 10.3. Tinker - REPL:

```bash
php artisan tinker

# Trong tinker:
$order = Order::find(1);
$order->status;
$order->customer->name;
```

### 10.4. Debugging với IDE:

**Xdebug** - Setup breakpoints trong IDE (PhpStorm, VS Code)

### 10.5. Query Logging:

```php
DB::enableQueryLog();

// Thực hiện queries
$orders = Order::with('customer')->get();

// Xem queries
dd(DB::getQueryLog());
```

---

## 11. CÁC ĐIỂM QUAN TRỌNG

### 11.1. Phân quyền theo kho:

**Warehouse Admin chỉ xem dữ liệu của kho mình:**
```php
if ($user->isWarehouseAdmin() && $user->warehouse_id) {
    $query->where('warehouse_id', $user->warehouse_id);
}
```

### 11.2. Quản lý warehouse_id và to_warehouse_id:

- **`warehouse_id`**: Kho hiện tại đang chứa đơn hàng
- **`to_warehouse_id`**: Kho đích sẽ nhận hàng (NULL nếu giao trực tiếp)

### 11.3. Trạng thái đơn hàng:

Các trạng thái chính:
- `pending` → `pickup_pending` → `picking_up` → `picked_up`
- `picked_up` → `in_warehouse`
- `in_warehouse` → `in_transit` (nếu chuyển kho) hoặc `out_for_delivery` (nếu giao hàng)
- `out_for_delivery` → `delivered` hoặc `failed`

### 11.4. WarehouseTransaction:

Ghi lại mọi giao dịch nhập/xuất kho:
- **Type `in`**: Nhập kho (từ tài xế hoặc từ kho khác)
- **Type `out`**: Xuất kho (cho shipper hoặc đi kho khác)

### 11.5. Tài xế:

- **Tài xế lấy hàng** (`pickup_driver_id`): Lấy hàng từ người gửi
- **Tài xế vận chuyển tỉnh** (`delivery_driver_id` khi `status = in_transit`): Vận chuyển giữa kho
- **Tài xế shipper** (`delivery_driver_id` khi `status = out_for_delivery`): Giao hàng cho khách

### 11.6. Validation:

Luôn validate input từ user:
```php
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
]);
```

### 11.7. Error Handling:

```php
try {
    $order = Order::create($data);
} catch (\Exception $e) {
    Log::error('Lỗi tạo đơn hàng', ['error' => $e->getMessage()]);
    return redirect()->back()->with('error', 'Có lỗi xảy ra');
}
```

---

## 12. CÁCH ĐỌC CODE MỚI

### Bước 1: Tìm Route
```php
// routes/web.php
Route::get('/orders', [OrderController::class, 'index']);
```

### Bước 2: Tìm Controller Method
```php
// app/Http/Controllers/OrderController.php
public function index(Request $request) {
    // Logic ở đây
}
```

### Bước 3: Tìm Model được sử dụng
```php
$orders = Order::with('customer')->get();
// → app/Models/Order.php
```

### Bước 4: Tìm Relationships
```php
// Order.php
public function customer() {
    return $this->belongsTo(Customer::class);
}
// → app/Models/Customer.php
```

### Bước 5: Tìm View
```php
return view('admin.orders.index', compact('orders'));
// → resources/views/admin/orders/index.blade.php
```

---

## 13. CÁC LỆNH ARTISAN HỮU ÍCH

```bash
# Xem tất cả routes
php artisan route:list

# Xem routes của một controller
php artisan route:list --name=orders

# Tạo migration
php artisan make:migration create_orders_table

# Tạo model
php artisan make:model Order

# Tạo controller
php artisan make:controller OrderController

# Tạo seeder
php artisan make:seeder OrderSeeder

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Xem logs
tail -f storage/logs/laravel.log
```

---

## 14. TIPS VÀ BEST PRACTICES

### 14.1. Đọc code từ trên xuống:

1. **Route** → Xem request đến đâu
2. **Controller** → Xem logic xử lý
3. **Model** → Xem dữ liệu
4. **View** → Xem output

### 14.2. Tìm kiếm trong code:

**Sử dụng IDE:**
- `Ctrl+Shift+F` (PhpStorm) - Tìm kiếm toàn bộ project
- `Ctrl+B` - Go to definition
- `Ctrl+Click` - Navigate to definition

**Sử dụng grep:**
```bash
grep -r "function calculateShippingFee" app/
```

### 14.3. Đọc comments:

Code có comments giải thích logic phức tạp:
```php
// Warehouse admin chỉ xem đơn hàng của kho mình
if ($user->isWarehouseAdmin() && $user->warehouse_id) {
    // ...
}
```

### 14.4. Hiểu naming conventions:

- **Controllers**: PascalCase, kết thúc bằng `Controller`
  - `OrderController`, `WarehouseController`
  
- **Models**: PascalCase, số ít
  - `Order`, `Customer`, `Warehouse`
  
- **Methods**: camelCase
  - `index()`, `store()`, `calculateShippingFee()`
  
- **Routes**: kebab-case
  - `/orders`, `/warehouses`, `/cod-reconciliations`

### 14.5. Follow the data flow:

```
User Action → Route → Controller → Model → Database
                                    ↓
                                  View ← Response
```

---

## 15. TÀI LIỆU THAM KHẢO

- **Laravel Documentation**: https://laravel.com/docs
- **Eloquent ORM**: https://laravel.com/docs/eloquent
- **Blade Templates**: https://laravel.com/docs/blade
- **Validation**: https://laravel.com/docs/validation

---

## KẾT LUẬN

Để hiểu code trong source này:

1. ✅ **Nắm vững Laravel MVC pattern**
2. ✅ **Hiểu Eloquent ORM và Relationships**
3. ✅ **Đọc code từ Route → Controller → Model → View**
4. ✅ **Sử dụng debugging tools (dd, Log, Tinker)**
5. ✅ **Follow naming conventions**
6. ✅ **Hiểu business logic (luồng nghiệp vụ)**
7. ✅ **Đọc comments và documentation**

**Chúc bạn code vui vẻ! 🚀**
