<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('batch_number', 50)->unique();
            $table->decimal('batch_size', 12, 2);
            $table->string('current_stage', 50)->default('mixing'); // mixing, filling, sekunder, completed
            $table->string('status', 20)->default('OPEN');          // OPEN, CLOSE, HOLD, REWORK
            $table->date('start_date');
            $table->date('close_date')->nullable();
            $table->decimal('total_output_mixing', 12, 2)->default(0);
            $table->decimal('total_output_filling', 12, 2)->default(0);
            $table->decimal('total_output_sekunder', 12, 2)->default(0);
            $table->decimal('final_yield', 6, 4)->nullable();      // e.g. 0.9903
            $table->decimal('final_balance', 12, 2)->nullable();    // actual - batch_size
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
