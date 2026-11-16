<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_id')->constrained('plants')->cascadeOnDelete();
            $table->date('report_date');
            $table->string('shift', 20);            // shift_1, shift_2, shift_3, middle
            $table->string('process_type', 50);     // mixing, filling, sekunder
            $table->string('line_name', 100)->nullable(); // e.g. AMIN, RENGGO, ARDO
            $table->string('pic_leader', 100);      // Wahyu, Alif, Dian, Nidom, dll.
            $table->integer('total_man_power');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_reports');
    }
};
