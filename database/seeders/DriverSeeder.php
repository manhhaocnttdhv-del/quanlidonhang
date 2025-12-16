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
        $ngheAnWarehouse = $warehouses->where('province', 'Nghệ An')->first() ?? $warehouses->first();
        $haNoiWarehouse = $warehouses->where('province', 'Hà Nội')->first();
        $hcmWarehouse = $warehouses->where('province', 'Hồ Chí Minh')->first();
        $daNangWarehouse = $warehouses->where('province', 'Đà Nẵng')->first();
        
        $firstNames = ['Nguyễn Văn', 'Trần Văn', 'Lê Văn', 'Phạm Văn', 'Hoàng Văn', 
                       'Vũ Văn', 'Đặng Văn', 'Bùi Văn', 'Đỗ Văn', 'Hồ Văn'];
        $lastNames = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        
        $vehicleTypes = ['Xe máy', 'Xe tải nhỏ', 'Xe tải lớn', 'Xe ba gác'];
        
        // Tạo tài xế cho mỗi kho
        foreach ($warehouses as $warehouse) {
            $provinceCode = '';
            if ($warehouse->province === 'Nghệ An') {
                $provinceCode = 'NA';
            } elseif ($warehouse->province === 'Hà Nội') {
                $provinceCode = 'HN';
            } elseif ($warehouse->province === 'Hồ Chí Minh') {
                $provinceCode = 'HCM';
            } elseif ($warehouse->province === 'Đà Nẵng') {
                $provinceCode = 'DN';
            } else {
                $provinceCode = strtoupper(substr($warehouse->province, 0, 2));
            }
            
            // Mỗi kho có 3 shipper và 1 intercity driver
            for ($i = 1; $i <= 3; $i++) {
                $name = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)] . ' ' . $i;
                $phone = '09' . rand(10000000, 99999999);
                $vehicleType = $vehicleTypes[array_rand($vehicleTypes)];
                
                Driver::create([
                    'code' => 'TX-' . $provinceCode . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'name' => $name,
                    'phone' => $phone,
                    'email' => strtolower(Str::slug($name)) . rand(1, 999) . '@smartpost.com',
                    'license_number' => $provinceCode . '-' . rand(100000, 999999),
                    'vehicle_type' => $vehicleType,
                    'vehicle_number' => rand(10, 99) . chr(65 + rand(0, 25)) . '-' . rand(10000, 99999),
                    'area' => $warehouse->district . ', ' . $warehouse->province,
                    'warehouse_id' => $warehouse->id,
                    'driver_type' => 'shipper',
                    'is_active' => rand(0, 100) > 10, // 90% active
                ]);
            }
            
            // 1 intercity driver cho mỗi kho
            $name = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)] . ' (VT)';
            $phone = '09' . rand(10000000, 99999999);
            
            Driver::create([
                'code' => 'TX-' . $provinceCode . '-VT-001',
                'name' => $name,
                'phone' => $phone,
                'email' => strtolower(Str::slug($name)) . rand(1, 999) . '@smartpost.com',
                'license_number' => $provinceCode . '-VT-' . rand(100000, 999999),
                'vehicle_type' => 'Xe tải lớn',
                'vehicle_number' => rand(10, 99) . chr(65 + rand(0, 25)) . '-' . rand(10000, 99999),
                'area' => 'Vận chuyển các tỉnh',
                'warehouse_id' => $warehouse->id,
                'driver_type' => 'intercity_driver',
                'is_active' => true,
            ]);
        }
    }
}
