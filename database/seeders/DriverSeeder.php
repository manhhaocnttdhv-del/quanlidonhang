<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Driver;
use App\Models\Warehouse;
use Illuminate\Support\Str;

class DriverSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy kho Nghệ An (kho mặc định)
        $ngheAnWarehouse = Warehouse::where('province', 'Nghệ An')
            ->orWhere('name', 'LIKE', '%Nghệ An%')
            ->orWhere('code', 'LIKE', '%NA%')
            ->first();
        
        if (!$ngheAnWarehouse) {
            $ngheAnWarehouse = Warehouse::first();
        }
        
        $drivers = [
            [
                'code' => 'TX-NA-001',
                'name' => 'Nguyễn Văn Tài Xế 1',
                'phone' => '0911111111',
                'email' => 'taixe1@smartpost.com',
                'license_number' => 'NA-123456',
                'vehicle_type' => 'Xe tải nhỏ',
                'vehicle_number' => '37A-12345',
                'area' => 'Thành phố Vinh, Nghệ An',
                'warehouse_id' => $ngheAnWarehouse->id,
                'driver_type' => 'shipper',
                'is_active' => true,
            ],
            [
                'code' => 'TX-NA-002',
                'name' => 'Trần Văn Tài Xế 2',
                'phone' => '0912222222',
                'email' => 'taixe2@smartpost.com',
                'license_number' => 'NA-234567',
                'vehicle_type' => 'Xe máy',
                'vehicle_number' => '37B-23456',
                'area' => 'Thành phố Vinh, Nghệ An',
                'warehouse_id' => $ngheAnWarehouse->id,
                'driver_type' => 'shipper',
                'is_active' => true,
            ],
            [
                'code' => 'TX-NA-003',
                'name' => 'Lê Văn Tài Xế 3',
                'phone' => '0913333333',
                'email' => 'taixe3@smartpost.com',
                'license_number' => 'NA-345678',
                'vehicle_type' => 'Xe tải lớn',
                'vehicle_number' => '37C-34567',
                'area' => 'Vận chuyển các tỉnh',
                'warehouse_id' => $ngheAnWarehouse->id,
                'driver_type' => 'intercity_driver',
                'is_active' => true,
            ],
            [
                'code' => 'TX-NA-004',
                'name' => 'Phạm Văn Tài Xế 4',
                'phone' => '0914444444',
                'email' => 'taixe4@smartpost.com',
                'license_number' => 'NA-456789',
                'vehicle_type' => 'Xe máy',
                'vehicle_number' => '37D-45678',
                'area' => 'Thành phố Vinh, Nghệ An',
                'warehouse_id' => $ngheAnWarehouse->id,
                'driver_type' => 'shipper',
                'is_active' => true,
            ],
        ];

        foreach ($drivers as $driver) {
            Driver::create($driver);
        }
    }
}
