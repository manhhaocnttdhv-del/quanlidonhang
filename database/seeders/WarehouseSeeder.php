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
                'code' => 'KHO-HCM-001',
                'name' => 'Kho Sài Gòn',
                'address' => '123 Đường Nguyễn Văn Linh, Phường Tân Thuận Đông',
                'province' => 'Thành phố Hồ Chí Minh',
                'district' => 'Quận 7',
                'ward' => 'Phường Tân Thuận Đông',
                'phone' => '0281234567',
                'manager_name' => 'Nguyễn Văn Quản Lý',
                'is_active' => true,
            ],
        ];

        // Sử dụng updateOrCreate để tránh duplicate và không cần truncate
        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(
                ['code' => $warehouse['code']],
                $warehouse
            );
        }
        
        // Vô hiệu hóa các kho không có trong danh sách (nếu có)
        $warehouseCodes = array_column($warehouses, 'code');
        Warehouse::whereNotIn('code', $warehouseCodes)
            ->update(['is_active' => false]);
    }
}
