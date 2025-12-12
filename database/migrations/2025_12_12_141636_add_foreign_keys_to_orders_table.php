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
        // Add foreign keys to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('pickup_driver_id')->references('id')->on('drivers')->nullOnDelete();
            $table->foreign('delivery_driver_id')->references('id')->on('drivers')->nullOnDelete();
            $table->foreign('route_id')->references('id')->on('routes')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
        });

        // Add foreign keys to order_statuses table
        Schema::table('order_statuses', function (Blueprint $table) {
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->foreign('driver_id')->references('id')->on('drivers')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Add foreign keys to drivers table
        Schema::table('drivers', function (Blueprint $table) {
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
        });

        // Add foreign keys to warehouse_transactions table
        Schema::table('warehouse_transactions', function (Blueprint $table) {
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('route_id')->references('id')->on('routes')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['pickup_driver_id']);
            $table->dropForeign(['delivery_driver_id']);
            $table->dropForeign(['route_id']);
            $table->dropForeign(['warehouse_id']);
        });

        Schema::table('order_statuses', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['driver_id']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
        });

        Schema::table('warehouse_transactions', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['order_id']);
            $table->dropForeign(['route_id']);
            $table->dropForeign(['created_by']);
        });
    }
};
