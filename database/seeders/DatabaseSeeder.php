<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ProvinceWardSeeder::class, // Load dữ liệu địa chỉ từ API 34tinhthanh.com (phải chạy đầu tiên)
            WarehouseSeeder::class,    // Phải chạy trước UserSeeder vì UserSeeder cần warehouses
            UserSeeder::class,
            CustomerSeeder::class,
            DriverSeeder::class,
            RouteSeeder::class,
            ShippingFeeSeeder::class,
            // OrderSeeder::class,
        ]);
    }
}
