<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('category', 50); // pangan_serbuk, cair_cod, minyak_col, ot
            $table->decimal('default_batch_size', 12, 2)->default(0);
            $table->string('unit', 20)->default('pcs'); // pcs, sachet, botol
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
