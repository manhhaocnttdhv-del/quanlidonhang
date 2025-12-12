<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'code' => 'KH' . strtoupper(Str::random(8)),
                'name' => 'Công ty TNHH ABC',
                'phone' => '0901111111',
                'email' => 'abc@example.com',
                'address' => '123 Đường ABC, Phường 1',
                'province' => 'Hồ Chí Minh',
                'district' => 'Quận 1',
                'ward' => 'Phường Bến Nghé',
                'tax_code' => '0123456789',
                'is_active' => true,
            ],
            [
                'code' => 'KH' . strtoupper(Str::random(8)),
                'name' => 'Cửa hàng XYZ',
                'phone' => '0902222222',
                'email' => 'xyz@example.com',
                'address' => '456 Đường XYZ, Phường 2',
                'province' => 'Hà Nội',
                'district' => 'Quận Hoàn Kiếm',
                'ward' => 'Phường Hàng Bông',
                'is_active' => true,
            ],
            [
                'code' => 'KH' . strtoupper(Str::random(8)),
                'name' => 'Shop Online DEF',
                'phone' => '0903333333',
                'email' => 'def@example.com',
                'address' => '789 Đường DEF',
                'province' => 'Đà Nẵng',
                'district' => 'Quận Hải Châu',
                'is_active' => true,
            ],
            [
                'code' => 'KH' . strtoupper(Str::random(8)),
                'name' => 'Nguyễn Văn A',
                'phone' => '0904444444',
                'email' => 'nguyenvana@example.com',
                'address' => '321 Đường GHI',
                'province' => 'Hồ Chí Minh',
                'district' => 'Quận 3',
                'is_active' => true,
            ],
            [
                'code' => 'KH' . strtoupper(Str::random(8)),
                'name' => 'Trần Thị B',
                'phone' => '0905555555',
                'address' => '654 Đường JKL',
                'province' => 'Hà Nội',
                'district' => 'Quận Cầu Giấy',
                'is_active' => true,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}
