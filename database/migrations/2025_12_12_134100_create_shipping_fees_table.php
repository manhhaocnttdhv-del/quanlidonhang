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
        Schema::create('shipping_fees', function (Blueprint $table) {
            $table->id();
            $table->string('from_province')->nullable();
            $table->string('from_district')->nullable();
            $table->string('to_province')->nullable();
            $table->string('to_district')->nullable();
            $table->string('service_type')->default('standard')->comment('express, standard, economy');
            $table->decimal('base_fee', 15, 2)->default(0)->comment('Phí cơ bản');
            $table->decimal('weight_fee_per_kg', 15, 2)->default(0)->comment('Phí theo kg');
            $table->decimal('cod_fee_percent', 5, 2)->default(0)->comment('Phí COD %');
            $table->decimal('min_weight', 10, 2)->default(0)->comment('Trọng lượng tối thiểu');
            $table->decimal('max_weight', 10, 2)->nullable()->comment('Trọng lượng tối đa');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_fees');
    }
};
