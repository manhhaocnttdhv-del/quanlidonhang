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
        $warehouses = Warehouse::all();
        
        $drivers = [
            [
                'code' => 'TX' . strtoupper(Str::random(8)),
                'name' => 'Nguyễn Văn Tài Xế 1',
                'phone' => '0911111111',
                'email' => 'taixe1@smartpost.com',
                'license_number' => 'A123456',
                'vehicle_type' => 'Xe tải nhỏ',
                'vehicle_number' => '51A-12345',
                'area' => 'Quận 1, Quận 3',
                'warehouse_id' => $warehouses->where('province', 'Hồ Chí Minh')->first()?->id,
                'is_active' => true,
            ],
            [
                'code' => 'TX' . strtoupper(Str::random(8)),
                'name' => 'Trần Văn Tài Xế 2',
                'phone' => '0912222222',
                'email' => 'taixe2@smartpost.com',
                'license_number' => 'B234567',
                'vehicle_type' => 'Xe máy',
                'vehicle_number' => '51B-23456',
                'area' => 'Quận 7, Quận 8',
                'warehouse_id' => $warehouses->where('province', 'Hồ Chí Minh')->first()?->id,
                'is_active' => true,
            ],
            [
                'code' => 'TX' . strtoupper(Str::random(8)),
                'name' => 'Lê Văn Tài Xế 3',
                'phone' => '0913333333',
                'license_number' => 'C345678',
                'vehicle_type' => 'Xe tải nhỏ',
                'vehicle_number' => '29A-34567',
                'area' => 'Quận Hoàn Kiếm, Quận Đống Đa',
                'warehouse_id' => $warehouses->where('province', 'Hà Nội')->first()?->id,
                'is_active' => true,
            ],
            [
                'code' => 'TX' . strtoupper(Str::random(8)),
                'name' => 'Phạm Văn Tài Xế 4',
                'phone' => '0914444444',
                'license_number' => 'D456789',
                'vehicle_type' => 'Xe máy',
                'vehicle_number' => '43A-45678',
                'area' => 'Quận Hải Châu',
                'warehouse_id' => $warehouses->where('province', 'Đà Nẵng')->first()?->id,
                'is_active' => true,
            ],
        ];

        foreach ($drivers as $driver) {
            Driver::create($driver);
        }
    }
}
