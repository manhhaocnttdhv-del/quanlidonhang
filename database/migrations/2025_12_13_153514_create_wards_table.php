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
        Schema::create('wards', function (Blueprint $table) {
            $table->string('ward_code', 20)->primary();
            $table->string('ward_name');
            $table->string('province_code', 10);
            $table->timestamps();

            $table->foreign('province_code')->references('province_code')->on('provinces')->onDelete('cascade');
            $table->index('province_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wards');
    }
};
