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
        Schema::create('cod_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('reconciliation_number')->unique()->comment('Số bảng kê');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('total_cod_amount', 15, 2)->default(0)->comment('Tổng COD');
            $table->decimal('total_shipping_fee', 15, 2)->default(0)->comment('Tổng phí vận chuyển');
            $table->decimal('total_amount', 15, 2)->default(0)->comment('Tổng tiền');
            $table->decimal('paid_amount', 15, 2)->default(0)->comment('Đã thanh toán');
            $table->decimal('remaining_amount', 15, 2)->default(0)->comment('Còn lại');
            $table->string('status')->default('pending')->comment('pending, paid, partial');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cod_reconciliations');
    }
};
