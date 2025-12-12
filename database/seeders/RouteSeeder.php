<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Route;
use Illuminate\Support\Str;

class RouteSeeder extends Seeder
{
    public function run(): void
    {
        $routes = [
            [
                'code' => 'TUYEN' . strtoupper(Str::random(6)),
                'name' => 'HCM - Hà Nội',
                'from_province' => 'Hồ Chí Minh',
                'from_district' => 'Quận 7',
                'to_province' => 'Hà Nội',
                'to_district' => 'Quận Đống Đa',
                'estimated_days' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'TUYEN' . strtoupper(Str::random(6)),
                'name' => 'HCM - Đà Nẵng',
                'from_province' => 'Hồ Chí Minh',
                'from_district' => 'Quận 7',
                'to_province' => 'Đà Nẵng',
                'to_district' => 'Quận Hải Châu',
                'estimated_days' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'TUYEN' . strtoupper(Str::random(6)),
                'name' => 'Hà Nội - Đà Nẵng',
                'from_province' => 'Hà Nội',
                'from_district' => 'Quận Đống Đa',
                'to_province' => 'Đà Nẵng',
                'to_district' => 'Quận Hải Châu',
                'estimated_days' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'TUYEN' . strtoupper(Str::random(6)),
                'name' => 'Nội thành HCM',
                'from_province' => 'Hồ Chí Minh',
                'from_district' => 'Quận 1',
                'to_province' => 'Hồ Chí Minh',
                'to_district' => 'Quận 3',
                'estimated_days' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'TUYEN' . strtoupper(Str::random(6)),
                'name' => 'Nội thành Hà Nội',
                'from_province' => 'Hà Nội',
                'from_district' => 'Quận Hoàn Kiếm',
                'to_province' => 'Hà Nội',
                'to_district' => 'Quận Cầu Giấy',
                'estimated_days' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($routes as $route) {
            Route::create($route);
        }
    }
}
