<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Warehouse;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = Warehouse::all();
        $ngheAnWarehouse = $warehouses->where('province', 'Nghệ An')->first();
        $haNoiWarehouse = $warehouses->where('province', 'Hà Nội')->first();
        $hcmWarehouse = $warehouses->where('province', 'Hồ Chí Minh')->first();
        $daNangWarehouse = $warehouses->where('province', 'Đà Nẵng')->first();

        // Tạo 30 khách hàng, phân bổ đều cho các kho
        $customerNames = [
            'Công ty TNHH', 'Cửa hàng', 'Shop Online', 'Doanh nghiệp', 'Công ty CP',
            'Thương mại', 'Dịch vụ', 'Xuất nhập khẩu', 'Sản xuất', 'Thương mại điện tử'
        ];
        
        $firstNames = ['Nguyễn Văn', 'Trần Thị', 'Lê Văn', 'Phạm Thị', 'Hoàng Văn', 
                       'Vũ Thị', 'Đặng Văn', 'Bùi Thị', 'Đỗ Văn', 'Hồ Thị'];
        $lastNames = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

        $totalCustomers = 30;
        $customersPerWarehouse = ceil($totalCustomers / $warehouses->count());

        for ($i = 1; $i <= $totalCustomers; $i++) {
            // Phân bổ đều giữa các kho
            $warehouseIndex = floor(($i - 1) / $customersPerWarehouse) % $warehouses->count();
            $warehouse = $warehouses->values()[$warehouseIndex];
            
            // Xác định tỉnh và địa chỉ dựa trên kho
            $province = $warehouse->province;
            $district = $warehouse->district ?? 'Quận ' . rand(1, 12);
            $ward = $warehouse->ward ?? 'Phường ' . rand(1, 20);
            
            // Tạo tên khách hàng
            if (rand(0, 100) <= 60) {
                // 60% là công ty/cửa hàng
                $name = $customerNames[array_rand($customerNames)] . ' ' . strtoupper(Str::random(3));
            } else {
                // 40% là cá nhân
                $name = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
            }
            
            // Tạo số điện thoại
            $phone = '09' . rand(10000000, 99999999);
            
            // Tạo email
            $email = strtolower(Str::slug($name)) . rand(1, 999) . '@example.com';
            
            Customer::create([
                'code' => 'KH' . strtoupper(Str::random(8)),
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'address' => rand(1, 999) . ' Đường ' . strtoupper(Str::random(5)) . ', ' . $ward,
                'province' => $province,
                'district' => $district,
                'ward' => $ward,
                'tax_code' => rand(0, 100) > 70 ? str_pad(rand(100000000, 999999999), 10, '0', STR_PAD_LEFT) : null,
                'warehouse_id' => $warehouse->id,
                'is_active' => rand(0, 100) > 10, // 90% active
            ]);
        }
    }
}
