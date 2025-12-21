<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\ShippingFee;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerOrderController extends Controller
{
    /**
     * Show registration form
     */
    public function showRegisterForm()
    {
        // If already logged in, redirect to dashboard
        if (Auth::guard('customer')->check() || (Auth::guard('web')->check() && Auth::guard('web')->user()->isCustomer())) {
            return redirect()->route('customer.dashboard');
        }
        
        return view('customer.register');
    }
    
    /**
     * Handle customer registration
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'password' => 'required|string|min:6|confirmed',
            'address' => 'required|string|max:500',
            'province' => 'required|string|max:255',
            'ward' => 'required|string|max:255',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ], [
            'name.required' => 'Vui lòng nhập họ và tên',
            'phone.required' => 'Vui lòng nhập số điện thoại',
            'phone.unique' => 'Số điện thoại này đã được sử dụng. Vui lòng sử dụng số điện thoại khác hoặc đăng nhập.',
            'email.email' => 'Email không hợp lệ',
            'email.unique' => 'Email này đã được sử dụng. Vui lòng sử dụng email khác hoặc đăng nhập.',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp',
            'address.required' => 'Vui lòng nhập địa chỉ chi tiết',
            'province.required' => 'Vui lòng chọn tỉnh/thành',
            'ward.required' => 'Vui lòng chọn phường/xã',
            'warehouse_id.exists' => 'Kho không hợp lệ',
        ]);
        
        // Generate customer code
        $code = 'KH' . strtoupper(Str::random(8));
        while (Customer::where('code', $code)->exists()) {
            $code = 'KH' . strtoupper(Str::random(8));
        }
        
        // Create customer
        $customer = Customer::create([
            'code' => $code,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'address' => $validated['address'],
            'province' => $validated['province'],
            'ward' => $validated['ward'],
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'is_active' => true,
        ]);
        
        // Auto login
        Auth::guard('customer')->login($customer);
        
        return redirect()->route('customer.dashboard')->with('success', 'Đăng ký thành công! Chào mừng bạn đến với SmartPost!');
    }
    
    /**
     * Display customer dashboard
     */
    public function dashboard()
    {
        // Get customer from customer guard or user guard
        $customer = Auth::guard('customer')->user();
        
        if (!$customer) {
            // Fallback: try to get from user guard
            $user = Auth::guard('web')->user();
            if ($user && $user->isCustomer()) {
                $customer = $user->customer;
            }
        }
        
        if (!$customer) {
            return redirect()->route('login')->with('error', 'Không tìm thấy thông tin khách hàng');
        }
        
        // Get customer's orders statistics
        $totalOrders = Order::where('customer_id', $customer->id)->count();
        $pendingOrders = Order::where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'pickup_pending', 'picking_up', 'picked_up', 'in_warehouse', 'in_transit'])
            ->count();
        $deliveredOrders = Order::where('customer_id', $customer->id)
            ->where('status', 'delivered')
            ->count();
        $recentOrders = Order::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        return view('customer.dashboard', compact('customer', 'totalOrders', 'pendingOrders', 'deliveredOrders', 'recentOrders'));
    }

    /**
     * Display a listing of customer's orders
     */
    public function index(Request $request)
    {
        // Get customer from customer guard or user guard
        $customer = Auth::guard('customer')->user();
        
        if (!$customer) {
            // Fallback: try to get from user guard
            $user = Auth::guard('web')->user();
            if ($user && $user->isCustomer()) {
                $customer = $user->customer;
            }
        }
        
        if (!$customer) {
            return redirect()->route('login')->with('error', 'Không tìm thấy thông tin khách hàng');
        }
        
        $query = Order::where('customer_id', $customer->id)
            ->with(['statuses' => function($q) {
                $q->orderBy('created_at', 'desc');
            }]);
        
        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('tracking_number')) {
            $query->where('tracking_number', 'like', '%' . $request->tracking_number . '%');
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $orders = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('customer.orders.index', compact('orders', 'customer'));
    }

    /**
     * Show the form for creating a new order
     */
    public function create()
    {
        // Get customer from customer guard or user guard
        $customer = Auth::guard('customer')->user();
        
        if (!$customer) {
            // Fallback: try to get from user guard
            $user = Auth::guard('web')->user();
            if ($user && $user->isCustomer()) {
                $customer = $user->customer;
            }
        }
        
        if (!$customer) {
            return redirect()->route('login')->with('error', 'Không tìm thấy thông tin khách hàng');
        }
        
        // Get default warehouse
        $warehouse = \App\Models\Warehouse::getDefaultWarehouse();
        
        return view('customer.orders.create', compact('customer', 'warehouse'));
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request)
    {
        // Get customer from customer guard or user guard
        $customer = Auth::guard('customer')->user();
        
        if (!$customer) {
            // Fallback: try to get from user guard
            $user = Auth::guard('web')->user();
            if ($user && $user->isCustomer()) {
                $customer = $user->customer;
            }
        }
        
        if (!$customer) {
            return redirect()->route('login')->with('error', 'Không tìm thấy thông tin khách hàng');
        }
        
        $validated = $request->validate([
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string',
            'receiver_province' => 'required|string|max:255',
            'receiver_district' => 'nullable|string|max:255',
            'receiver_ward' => 'required|string|max:255',
            'item_type' => 'nullable|string|max:255',
            'weight' => 'required|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'cod_amount' => 'nullable|numeric|min:0',
            'service_type' => 'nullable|in:express,standard,economy',
            'is_fragile' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'pickup_method' => 'required|in:driver,warehouse',
            'to_warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        // Generate tracking number
        $trackingNumber = $this->generateTrackingNumber();

        // Get default warehouse
        $originWarehouse = \App\Models\Warehouse::getDefaultWarehouse();
        
        // Set sender info from customer (tự động điền từ thông tin customer)
        // Nếu customer chưa có đầy đủ thông tin, sử dụng thông tin mặc định
        $validated['sender_name'] = $customer->name ?? 'Khách hàng';
        $validated['sender_phone'] = $customer->phone ?? '';
        $validated['sender_address'] = $customer->address ?? '';
        $validated['sender_province'] = $customer->province ?? $originWarehouse->province ?? 'Nghệ An';
        $validated['sender_district'] = $customer->district ?? '';
        $validated['sender_ward'] = $customer->ward ?? '';
        
        // Calculate shipping fee
        $fromProvince = $validated['sender_province'];
        $shippingFee = $this->calculateShippingFeeInternal(
            $fromProvince,
            $validated['sender_district'] ?? '',
            $validated['receiver_province'] ?? '',
            $validated['receiver_district'] ?? '',
            $validated['weight'],
            $validated['service_type'] ?? 'standard',
            $validated['cod_amount'] ?? 0
        );
        
        // Determine initial status
        $pickupMethod = $validated['pickup_method'] ?? 'driver';
        $initialStatus = $pickupMethod === 'warehouse' ? 'in_warehouse' : 'pending';
        
        // Calculate pickup fee (20,000 đ nếu tài xế đến lấy)
        $pickupFee = ($pickupMethod === 'driver') ? 20000 : 0;
        
        // Total fee = shipping fee + pickup fee
        $totalShippingFee = $shippingFee + $pickupFee;
        
        unset($validated['pickup_method']);
        unset($validated['shipping_fee']);

        // Create order
        $orderData = array_merge($validated, [
            'tracking_number' => $trackingNumber,
            'customer_id' => $customer->id,
            'shipping_fee' => $totalShippingFee, // Tổng phí bao gồm cả phí lấy hàng
            'status' => $initialStatus,
            'warehouse_id' => $originWarehouse->id ?? null,
            'to_warehouse_id' => $validated['to_warehouse_id'] ?? null,
            'created_by' => Auth::guard('web')->id() ?? null,
        ]);
        
        $order = Order::create($orderData);

        // Create initial status
        if ($pickupMethod === 'warehouse') {
            $warehouseName = $originWarehouse ? $originWarehouse->name : 'Nghệ An';
            OrderStatus::create([
                'order_id' => $order->id,
                'status' => 'in_warehouse',
                'notes' => "Người gửi đã đưa hàng đến kho {$warehouseName}",
                'warehouse_id' => $originWarehouse->id ?? null,
                'updated_by' => Auth::guard('web')->id() ?? null,
            ]);
            
            \App\Models\WarehouseTransaction::create([
                'warehouse_id' => $originWarehouse->id,
                'order_id' => $order->id,
                'type' => 'in',
                'transaction_date' => now(),
                'notes' => "Người gửi đưa hàng đến kho {$warehouseName}",
                'created_by' => Auth::guard('web')->id() ?? null,
            ]);
        } else {
            OrderStatus::create([
                'order_id' => $order->id,
                'status' => 'pending',
                'notes' => 'Đơn hàng mới được tạo, chờ tài xế đến lấy',
                'warehouse_id' => $originWarehouse->id ?? null,
                'updated_by' => Auth::guard('web')->id() ?? null,
            ]);
        }

        return redirect()->route('customer.orders.show', $order->id)
            ->with('success', 'Đơn hàng đã được tạo thành công. Mã vận đơn: ' . $trackingNumber);
    }

    /**
     * Display the specified order
     */
    public function show(string $id)
    {
        // Get customer from customer guard or user guard
        $customer = Auth::guard('customer')->user();
        
        if (!$customer) {
            // Fallback: try to get from user guard
            $user = Auth::guard('web')->user();
            if ($user && $user->isCustomer()) {
                $customer = $user->customer;
            }
        }
        
        if (!$customer) {
            return redirect()->route('login')->with('error', 'Không tìm thấy thông tin khách hàng');
        }
        
        $order = Order::with([
            'customer',
            'pickupDriver',
            'deliveryDriver',
            'route',
            'warehouse',
            'toWarehouse',
            'statuses' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
        ])->findOrFail($id);
        
        // Check if order belongs to customer
        if ($order->customer_id !== $customer->id) {
            abort(403, 'Bạn không có quyền xem đơn hàng này');
        }
        
        return view('customer.orders.show', compact('order', 'customer'));
    }

    /**
     * Generate unique tracking number
     */
    private function generateTrackingNumber(): string
    {
        do {
            $trackingNumber = 'VD' . date('Ymd') . strtoupper(Str::random(6));
        } while (Order::where('tracking_number', $trackingNumber)->exists());

        return $trackingNumber;
    }

    /**
     * API endpoint to calculate shipping fee
     */
    public function calculateShippingFee(Request $request)
    {
        $validated = $request->validate([
            'from_province' => 'required|string',
            'from_district' => 'nullable|string',
            'to_province' => 'required|string',
            'to_district' => 'nullable|string',
            'weight' => 'required|numeric|min:0',
            'service_type' => 'required|in:express,standard,economy',
            'cod_amount' => 'nullable|numeric|min:0',
        ]);

        $fee = $this->calculateShippingFeeInternal(
            $validated['from_province'],
            $validated['from_district'] ?? '',
            $validated['to_province'],
            $validated['to_district'] ?? '',
            $validated['weight'],
            $validated['service_type'],
            $validated['cod_amount'] ?? 0
        );

        return response()->json([
            'fee' => $fee,
        ]);
    }

    /**
     * Calculate shipping fee (internal method)
     */
    private function calculateShippingFeeInternal(
        string $fromProvince,
        string $fromDistrict,
        string $toProvince,
        string $toDistrict,
        float $weight,
        string $serviceType,
        float $codAmount
    ): float {
        // Priority 1: Find exact match (province + district)
        $shippingFee = ShippingFee::where('from_province', $fromProvince)
            ->where('from_district', $fromDistrict ?? '')
            ->where('to_province', $toProvince)
            ->where('to_district', $toDistrict ?? '')
            ->where('service_type', $serviceType)
            ->where('is_active', true)
            ->first();

        // Priority 2: Find by province only
        if (!$shippingFee) {
            $shippingFee = ShippingFee::where('from_province', $fromProvince)
                ->where(function($query) {
                    $query->whereNull('from_district')
                          ->orWhere('from_district', '');
                })
                ->where('to_province', $toProvince)
                ->where(function($query) {
                    $query->whereNull('to_district')
                          ->orWhere('to_district', '');
                })
                ->where('service_type', $serviceType)
                ->where('is_active', true)
                ->first();
        }

        // Priority 3: Find by region
        if (!$shippingFee) {
            $fromRegion = $this->getRegion($fromProvince);
            $toRegion = $this->getRegion($toProvince);
            
            if ($fromRegion === $toRegion) {
                $shippingFee = $this->getRegionalFee($fromRegion, $serviceType);
            } else {
                $shippingFee = $this->getInterRegionalFee($fromRegion, $toRegion, $serviceType);
            }
        }

        // Priority 4: Default fee
        if (!$shippingFee) {
            $defaultFees = [
                'express' => ['base_fee' => 50000, 'weight_fee_per_kg' => 10000, 'cod_fee_percent' => 2],
                'standard' => ['base_fee' => 30000, 'weight_fee_per_kg' => 8000, 'cod_fee_percent' => 1.5],
                'economy' => ['base_fee' => 20000, 'weight_fee_per_kg' => 6000, 'cod_fee_percent' => 1.5],
            ];
            
            $defaultFee = $defaultFees[$serviceType] ?? ['base_fee' => 30000, 'weight_fee_per_kg' => 8000, 'cod_fee_percent' => 1.5];
            
            $baseFee = $defaultFee['base_fee'];
            $minWeight = 0.5;
            $weightFee = max(0, $weight - $minWeight) * $defaultFee['weight_fee_per_kg'];
            $codFee = $codAmount * ($defaultFee['cod_fee_percent'] / 100);
            
            return $baseFee + $weightFee + $codFee;
        }

        // Handle both object and stdClass
        if (is_object($shippingFee)) {
            $baseFee = $shippingFee->base_fee ?? 0;
            $weightFeePerKg = $shippingFee->weight_fee_per_kg ?? 0;
            $codFeePercent = $shippingFee->cod_fee_percent ?? 0;
            $minWeight = $shippingFee->min_weight ?? 0.5;
        } else {
            $baseFee = $shippingFee['base_fee'] ?? 0;
            $weightFeePerKg = $shippingFee['weight_fee_per_kg'] ?? 0;
            $codFeePercent = $shippingFee['cod_fee_percent'] ?? 0;
            $minWeight = $shippingFee['min_weight'] ?? 0.5;
        }
        
        $weightFee = max(0, $weight - $minWeight) * $weightFeePerKg;
        $codFee = $codAmount * ($codFeePercent / 100);
        
        return $baseFee + $weightFee + $codFee;
    }

    /**
     * Get region of province
     */
    private function getRegion(string $province): string
    {
        $normalizedProvince = trim(preg_replace('/^(Thành phố|Tỉnh)\s+/', '', $province));
        
        $northernProvinces = [
            'Hà Nội', 'Hải Phòng', 'Hà Giang', 'Cao Bằng', 'Bắc Kạn', 'Tuyên Quang', 
            'Lào Cai', 'Điện Biên', 'Lai Châu', 'Sơn La', 'Yên Bái', 'Hòa Bình', 
            'Thái Nguyên', 'Lạng Sơn', 'Quảng Ninh', 'Bắc Giang', 'Phú Thọ', 
            'Vĩnh Phúc', 'Bắc Ninh', 'Hải Dương', 'Hưng Yên', 'Thái Bình', 
            'Hà Nam', 'Nam Định', 'Ninh Bình'
        ];
        
        $centralProvinces = [
            'Thanh Hóa', 'Nghệ An', 'Hà Tĩnh', 'Quảng Bình', 'Quảng Trị', 
            'Thừa Thiên Huế', 'Đà Nẵng', 'Quảng Nam', 'Quảng Ngãi', 'Bình Định', 
            'Phú Yên', 'Khánh Hòa', 'Ninh Thuận', 'Bình Thuận', 'Kon Tum', 
            'Gia Lai', 'Đắk Lắk', 'Đắk Nông', 'Lâm Đồng'
        ];
        
        if (in_array($normalizedProvince, $northernProvinces, true)) {
            return 'north';
        } elseif (in_array($normalizedProvince, $centralProvinces, true)) {
            return 'central';
        }
        
        if (in_array($province, $northernProvinces, true)) {
            return 'north';
        } elseif (in_array($province, $centralProvinces, true)) {
            return 'central';
        }
        
        return 'south';
    }

    /**
     * Get regional shipping fee
     */
    private function getRegionalFee(string $region, string $serviceType)
    {
        $regionalFees = [
            'north' => [
                'express' => ['base_fee' => 35000, 'weight_fee_per_kg' => 7000, 'cod_fee_percent' => 1.5],
                'standard' => ['base_fee' => 25000, 'weight_fee_per_kg' => 5000, 'cod_fee_percent' => 1.5],
                'economy' => ['base_fee' => 18000, 'weight_fee_per_kg' => 3000, 'cod_fee_percent' => 1.5],
            ],
            'central' => [
                'express' => ['base_fee' => 40000, 'weight_fee_per_kg' => 8000, 'cod_fee_percent' => 1.8],
                'standard' => ['base_fee' => 28000, 'weight_fee_per_kg' => 6000, 'cod_fee_percent' => 1.8],
                'economy' => ['base_fee' => 20000, 'weight_fee_per_kg' => 4000, 'cod_fee_percent' => 1.8],
            ],
            'south' => [
                'express' => ['base_fee' => 35000, 'weight_fee_per_kg' => 7000, 'cod_fee_percent' => 1.5],
                'standard' => ['base_fee' => 25000, 'weight_fee_per_kg' => 5000, 'cod_fee_percent' => 1.5],
                'economy' => ['base_fee' => 18000, 'weight_fee_per_kg' => 3000, 'cod_fee_percent' => 1.5],
            ],
        ];

        $fee = $regionalFees[$region][$serviceType] ?? null;
        if (!$fee) return null;

        return (object) [
            'base_fee' => $fee['base_fee'],
            'weight_fee_per_kg' => $fee['weight_fee_per_kg'],
            'cod_fee_percent' => $fee['cod_fee_percent'],
            'min_weight' => 0.5,
            'max_weight' => 50,
        ];
    }

    /**
     * Get inter-regional shipping fee
     */
    private function getInterRegionalFee(string $fromRegion, string $toRegion, string $serviceType)
    {
        if ($fromRegion === $toRegion) {
            return $this->getRegionalFee($fromRegion, $serviceType);
        }

        $isAdjacent = 
            ($fromRegion === 'north' && $toRegion === 'central') ||
            ($fromRegion === 'central' && $toRegion === 'north') ||
            ($fromRegion === 'central' && $toRegion === 'south') ||
            ($fromRegion === 'south' && $toRegion === 'central');

        $isFar = 
            ($fromRegion === 'north' && $toRegion === 'south') ||
            ($fromRegion === 'south' && $toRegion === 'north');

        if ($isAdjacent) {
            $fees = [
                'express' => ['base_fee' => 60000, 'weight_fee_per_kg' => 12000, 'cod_fee_percent' => 2],
                'standard' => ['base_fee' => 45000, 'weight_fee_per_kg' => 9000, 'cod_fee_percent' => 2],
                'economy' => ['base_fee' => 35000, 'weight_fee_per_kg' => 7000, 'cod_fee_percent' => 2],
            ];
        } elseif ($isFar) {
            $fees = [
                'express' => ['base_fee' => 80000, 'weight_fee_per_kg' => 15000, 'cod_fee_percent' => 2.5],
                'standard' => ['base_fee' => 60000, 'weight_fee_per_kg' => 12000, 'cod_fee_percent' => 2.5],
                'economy' => ['base_fee' => 45000, 'weight_fee_per_kg' => 9000, 'cod_fee_percent' => 2.5],
            ];
        } else {
            return null;
        }

        $fee = $fees[$serviceType] ?? null;
        if (!$fee) return null;

        return (object) [
            'base_fee' => $fee['base_fee'],
            'weight_fee_per_kg' => $fee['weight_fee_per_kg'],
            'cod_fee_percent' => $fee['cod_fee_percent'],
            'min_weight' => 0.5,
            'max_weight' => 50,
        ];
    }
}

