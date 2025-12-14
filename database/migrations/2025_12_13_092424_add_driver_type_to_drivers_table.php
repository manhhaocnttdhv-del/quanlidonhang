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
        Schema::table('drivers', function (Blueprint $table) {
            $table->enum('driver_type', ['shipper', 'intercity_driver'])->default('shipper')->after('warehouse_id')->comment('shipper: Tài xế shipper, intercity_driver: Tài xế vận chuyển tỉnh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn('driver_type');
        });
    }
};
