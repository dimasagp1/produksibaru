<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('production_reports')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('machine_id')->nullable()->constrained('machines')->nullOnDelete();
            $table->integer('duration_minutes');
            $table->decimal('qty_target_per_shift_mp', 12, 2)->nullable();
            $table->decimal('actual_output', 12, 2);
            $table->decimal('qty_target_pengerjaan', 12, 2);
            $table->decimal('balance_target', 12, 2);
            $table->decimal('productivity', 6, 4);
            $table->string('batch_status', 20);      // OPEN, CLOSE
            $table->integer('downtime_planned_minutes')->default(0);
            $table->integer('downtime_unplanned_minutes')->default(0);
            $table->decimal('reject_qty', 12, 2)->default(0);
            // OEE Fields
            $table->integer('loading_time_minutes')->nullable();
            $table->decimal('availability_rate', 6, 4)->nullable();
            $table->decimal('performance_rate', 6, 4)->nullable();
            $table->decimal('quality_rate', 6, 4)->nullable();
            $table->decimal('oee_score', 6, 4)->nullable();
            // Yield Field
            $table->decimal('cumulative_yield', 6, 4)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_report_items');
    }
};
