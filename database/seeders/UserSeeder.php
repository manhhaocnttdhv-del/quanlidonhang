<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy thông tin kho Hà Nội và Sài Gòn
        $warehouseHN = Warehouse::where('code', 'KHO-HN-001')->first();
        $warehouseSG = Warehouse::where('code', 'KHO-HCM-001')->first();

        $users = [
            [
                'name' => 'Admin Tổng',
                'email' => 'superadmin@smartpost.com',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'phone' => '0901234566',
                'is_active' => true,
                'warehouse_id' => null,
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@smartpost.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '0901234567',
                'is_active' => true,
                'warehouse_id' => null,
            ],
            [
                'name' => 'Quản lý',
                'email' => 'manager@smartpost.com',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'phone' => '0901234568',
                'is_active' => true,
                'warehouse_id' => null,
            ],
            [
                'name' => 'Điều phối viên',
                'email' => 'dispatcher@smartpost.com',
                'password' => Hash::make('password'),
                'role' => 'dispatcher',
                'phone' => '0901234569',
                'is_active' => true,
                'warehouse_id' => null,
            ],
            [
                'name' => 'Nhân viên kho',
                'email' => 'warehouse@smartpost.com',
                'password' => Hash::make('password'),
                'role' => 'warehouse_staff',
                'phone' => '0901234570',
                'is_active' => true,
                'warehouse_id' => null,
            ],
            [
                'name' => 'Nhân viên',
                'email' => 'staff@smartpost.com',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'phone' => '0901234571',
                'is_active' => true,
                'warehouse_id' => null,
            ],
        ];

        // Thêm admin kho Hà Nội
        if ($warehouseHN) {
            $users[] = [
                'name' => 'Admin Kho Hà Nội',
                'email' => 'admin.warehouse.hn@smartpost.com',
                'password' => Hash::make('password'),
                'role' => 'warehouse_admin',
                'phone' => '0901234572',
                'is_active' => true,
                'warehouse_id' => $warehouseHN->id,
            ];
        }

        // Thêm admin kho Sài Gòn
        if ($warehouseSG) {
            $users[] = [
                'name' => 'Admin Kho Sài Gòn',
                'email' => 'admin.warehouse.sg@smartpost.com',
                'password' => Hash::make('password'),
                'role' => 'warehouse_admin',
                'phone' => '0901234573',
                'is_active' => true,
                'warehouse_id' => $warehouseSG->id,
            ];
        }

        // Lấy thông tin kho Nghệ An
        $warehouseNA = Warehouse::where('code', 'KHO-NA-001')->first();

        // Thêm admin kho Nghệ An
        if ($warehouseNA) {
            $users[] = [
                'name' => 'Admin Kho Nghệ An',
                'email' => 'admin.warehouse.na@smartpost.com',
                'password' => Hash::make('password'),
                'role' => 'warehouse_admin',
                'phone' => '0901234574',
                'is_active' => true,
                'warehouse_id' => $warehouseNA->id,
            ];
        }

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
