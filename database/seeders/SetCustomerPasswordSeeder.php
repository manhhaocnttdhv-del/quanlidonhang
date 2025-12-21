<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class SetCustomerPasswordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Set password mặc định cho tất cả customer chưa có password
        $customers = Customer::whereNull('password')
            ->orWhere('password', '')
            ->get();
        
        $defaultPassword = '123456'; // Password mặc định
        
        foreach ($customers as $customer) {
            $customer->password = Hash::make($defaultPassword);
            $customer->save();
            
            $this->command->info("Đã set password cho customer: {$customer->name} (ID: {$customer->id}, Email: {$customer->email}, Phone: {$customer->phone})");
        }
        
        $this->command->info("Đã set password mặc định '{$defaultPassword}' cho {$customers->count()} customer(s).");
        $this->command->warn("Lưu ý: Vui lòng đổi mật khẩu sau khi đăng nhập!");
    }
}
