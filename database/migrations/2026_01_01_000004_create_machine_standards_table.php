<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_standards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('process_type', 50); // mixing, filling, sekunder
            $table->decimal('ideal_speed_per_minute', 8, 2); // e.g. 24.00, 28.00, 22.00, 12.00
            $table->decimal('target_shift_per_mp', 12, 2);   // e.g. 7000.00, 8500.00
            $table->integer('standard_man_power')->default(1);
            $table->decimal('net_hours_per_shift', 4, 2)->default(6.50); // 6.5 jam = 390 menit
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_standards');
    }
};
