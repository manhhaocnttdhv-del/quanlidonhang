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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique()->comment('Mã vận đơn');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sender_name');
            $table->string('sender_phone');
            $table->text('sender_address');
            $table->string('sender_province')->nullable();
            $table->string('sender_district')->nullable();
            $table->string('sender_ward')->nullable();
            $table->string('receiver_name');
            $table->string('receiver_phone');
            $table->text('receiver_address');
            $table->string('receiver_province')->nullable();
            $table->string('receiver_district')->nullable();
            $table->string('receiver_ward')->nullable();
            $table->string('item_type')->nullable()->comment('Loại hàng');
            $table->decimal('weight', 10, 2)->default(0)->comment('Trọng lượng (kg)');
            $table->decimal('length', 10, 2)->nullable()->comment('Chiều dài (cm)');
            $table->decimal('width', 10, 2)->nullable()->comment('Chiều rộng (cm)');
            $table->decimal('height', 10, 2)->nullable()->comment('Chiều cao (cm)');
            $table->decimal('cod_amount', 15, 2)->default(0)->comment('Tiền thu hộ COD');
            $table->decimal('shipping_fee', 15, 2)->default(0)->comment('Phí vận chuyển');
            $table->string('service_type')->default('standard')->comment('Loại dịch vụ: express, standard, economy');
            $table->string('status')->default('pending')->comment('Trạng thái: pending, pickup_pending, picking_up, picked_up, in_warehouse, in_transit, out_for_delivery, delivered, failed, returned');
            $table->unsignedBigInteger('pickup_driver_id')->nullable();
            $table->unsignedBigInteger('delivery_driver_id')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->timestamp('pickup_scheduled_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivery_scheduled_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->text('failure_reason')->nullable();
            $table->boolean('is_fragile')->default(false);
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
        Schema::dropIfExists('orders');
    }
};
