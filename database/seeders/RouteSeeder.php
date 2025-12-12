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
                'code' => 'NA-HN',
                'name' => 'Nghệ An - Hà Nội',
                'from_province' => 'Nghệ An',
                'to_province' => 'Hà Nội',
                'estimated_days' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'NA-HCM',
                'name' => 'Nghệ An - Hồ Chí Minh',
                'from_province' => 'Nghệ An',
                'to_province' => 'Hồ Chí Minh',
                'estimated_days' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'NA-DN',
                'name' => 'Nghệ An - Đà Nẵng',
                'from_province' => 'Nghệ An',
                'to_province' => 'Đà Nẵng',
                'estimated_days' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'NA-HP',
                'name' => 'Nghệ An - Hải Phòng',
                'from_province' => 'Nghệ An',
                'to_province' => 'Hải Phòng',
                'estimated_days' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'NA-CT',
                'name' => 'Nghệ An - Cần Thơ',
                'from_province' => 'Nghệ An',
                'to_province' => 'Cần Thơ',
                'estimated_days' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($routes as $route) {
            Route::create($route);
        }
    }
}
