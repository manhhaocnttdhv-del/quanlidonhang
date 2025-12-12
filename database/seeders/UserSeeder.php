<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@smartpost.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '0901234567',
                'is_active' => true,
            ],
            [
                'name' => 'Quản lý',
                'email' => 'manager@smartpost.com',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'phone' => '0901234568',
                'is_active' => true,
            ],
            [
                'name' => 'Điều phối viên',
                'email' => 'dispatcher@smartpost.com',
                'password' => Hash::make('password'),
                'role' => 'dispatcher',
                'phone' => '0901234569',
                'is_active' => true,
            ],
            [
                'name' => 'Nhân viên kho',
                'email' => 'warehouse@smartpost.com',
                'password' => Hash::make('password'),
                'role' => 'warehouse_staff',
                'phone' => '0901234570',
                'is_active' => true,
            ],
            [
                'name' => 'Nhân viên',
                'email' => 'staff@smartpost.com',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'phone' => '0901234571',
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
