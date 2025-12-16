<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sửa cột shipping_fee thành nullable
        // Phí vận chuyển chỉ được nhập khi giao hàng thành công, không lưu khi tạo đơn hàng
        // Sử dụng DB::statement để đảm bảo thay đổi được áp dụng
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE `orders` MODIFY COLUMN `shipping_fee` DECIMAL(15, 2) NULL DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Khôi phục lại default(0) và không nullable
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE `orders` MODIFY COLUMN `shipping_fee` DECIMAL(15, 2) NOT NULL DEFAULT 0');
    }
};
