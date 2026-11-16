<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downtime_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_item_id')->constrained('production_report_items')->cascadeOnDelete();
            $table->string('downtime_category', 50); // mesin, material, cleaning, qc, listrik, dll.
            $table->string('downtime_type', 50);     // planned, unplanned
            $table->integer('duration_minutes');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downtime_logs');
    }
};
