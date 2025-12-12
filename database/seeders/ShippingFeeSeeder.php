<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShippingFee;

class ShippingFeeSeeder extends Seeder
{
    public function run(): void
    {
        $shippingFees = [
            // Nghệ An - Hà Nội
            [
                'from_province' => 'Nghệ An',
                'from_district' => '',
                'to_province' => 'Hà Nội',
                'to_district' => '',
                'service_type' => 'standard',
                'base_fee' => 45000,
                'weight_fee_per_kg' => 9000,
                'cod_fee_percent' => 2,
                'min_weight' => 0.5,
                'max_weight' => 50,
                'is_active' => true,
            ],
            [
                'from_province' => 'Nghệ An',
                'from_district' => '',
                'to_province' => 'Hà Nội',
                'to_district' => '',
                'service_type' => 'express',
                'base_fee' => 65000,
                'weight_fee_per_kg' => 13000,
                'cod_fee_percent' => 2,
                'min_weight' => 0.5,
                'max_weight' => 50,
                'is_active' => true,
            ],
            [
                'from_province' => 'Nghệ An',
                'from_district' => '',
                'to_province' => 'Hà Nội',
                'to_district' => '',
                'service_type' => 'economy',
                'base_fee' => 35000,
                'weight_fee_per_kg' => 7000,
                'cod_fee_percent' => 2,
                'min_weight' => 0.5,
                'max_weight' => 50,
                'is_active' => true,
            ],
            // Nghệ An - Hồ Chí Minh
            [
                'from_province' => 'Nghệ An',
                'from_district' => '',
                'to_province' => 'Hồ Chí Minh',
                'to_district' => '',
                'service_type' => 'standard',
                'base_fee' => 60000,
                'weight_fee_per_kg' => 12000,
                'cod_fee_percent' => 2.5,
                'min_weight' => 0.5,
                'max_weight' => 50,
                'is_active' => true,
            ],
            [
                'from_province' => 'Nghệ An',
                'from_district' => '',
                'to_province' => 'Hồ Chí Minh',
                'to_district' => '',
                'service_type' => 'express',
                'base_fee' => 85000,
                'weight_fee_per_kg' => 17000,
                'cod_fee_percent' => 2.5,
                'min_weight' => 0.5,
                'max_weight' => 50,
                'is_active' => true,
            ],
            [
                'from_province' => 'Nghệ An',
                'from_district' => '',
                'to_province' => 'Hồ Chí Minh',
                'to_district' => '',
                'service_type' => 'economy',
                'base_fee' => 45000,
                'weight_fee_per_kg' => 9000,
                'cod_fee_percent' => 2.5,
                'min_weight' => 0.5,
                'max_weight' => 50,
                'is_active' => true,
            ],
            // Nghệ An - Đà Nẵng
            [
                'from_province' => 'Nghệ An',
                'from_district' => '',
                'to_province' => 'Đà Nẵng',
                'to_district' => '',
                'service_type' => 'standard',
                'base_fee' => 35000,
                'weight_fee_per_kg' => 7000,
                'cod_fee_percent' => 1.8,
                'min_weight' => 0.5,
                'max_weight' => 50,
                'is_active' => true,
            ],
            [
                'from_province' => 'Nghệ An',
                'from_district' => '',
                'to_province' => 'Đà Nẵng',
                'to_district' => '',
                'service_type' => 'express',
                'base_fee' => 50000,
                'weight_fee_per_kg' => 10000,
                'cod_fee_percent' => 1.8,
                'min_weight' => 0.5,
                'max_weight' => 50,
                'is_active' => true,
            ],
            // Nghệ An - Các tỉnh miền Bắc
            [
                'from_province' => 'Nghệ An',
                'from_district' => '',
                'to_province' => 'Hải Phòng',
                'to_district' => '',
                'service_type' => 'standard',
                'base_fee' => 40000,
                'weight_fee_per_kg' => 8000,
                'cod_fee_percent' => 2,
                'min_weight' => 0.5,
                'max_weight' => 50,
                'is_active' => true,
            ],
            // Nghệ An - Các tỉnh miền Nam
            [
                'from_province' => 'Nghệ An',
                'from_district' => '',
                'to_province' => 'Cần Thơ',
                'to_district' => '',
                'service_type' => 'standard',
                'base_fee' => 65000,
                'weight_fee_per_kg' => 13000,
                'cod_fee_percent' => 2.5,
                'min_weight' => 0.5,
                'max_weight' => 50,
                'is_active' => true,
            ],
        ];

        foreach ($shippingFees as $fee) {
            ShippingFee::create($fee);
        }
    }
}
