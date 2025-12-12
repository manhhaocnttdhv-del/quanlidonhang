<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            [
                'code' => 'KHO-NA-001',
                'name' => 'Kho Nghệ An',
                'address' => 'Số 1 Đường Quang Trung, Phường Hưng Bình',
                'province' => 'Nghệ An',
                'district' => 'Thành phố Vinh',
                'ward' => 'Phường Hưng Bình',
                'phone' => '0238123456',
                'manager_name' => 'Nguyễn Văn Quản Lý',
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'code' => 'KHO-HCM-001',
                'name' => 'Kho Hồ Chí Minh',
                'address' => '123 Đường Nguyễn Văn Linh, Phường Tân Thuận Đông',
                'province' => 'Hồ Chí Minh',
                'district' => 'Quận 7',
                'ward' => 'Phường Tân Thuận Đông',
                'phone' => '0281234567',
                'manager_name' => 'Nguyễn Văn Quản Lý',
                'is_active' => true,
            ],
            [
                'code' => 'KHO-HN-001',
                'name' => 'Kho Hà Nội',
                'address' => '456 Đường Láng, Phường Láng Thượng',
                'province' => 'Hà Nội',
                'district' => 'Quận Đống Đa',
                'ward' => 'Phường Láng Thượng',
                'phone' => '0241234567',
                'manager_name' => 'Trần Văn Quản Lý',
                'is_active' => true,
            ],
            [
                'code' => 'KHO-DN-001',
                'name' => 'Kho Đà Nẵng',
                'address' => '789 Đường Nguyễn Văn Linh, Phường Hải Châu',
                'province' => 'Đà Nẵng',
                'district' => 'Quận Hải Châu',
                'ward' => 'Phường Hải Châu',
                'phone' => '0236123456',
                'manager_name' => 'Lê Văn Quản Lý',
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::create($warehouse);
        }
    }
}
