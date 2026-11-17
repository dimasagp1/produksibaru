<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_reports', function (Blueprint $table) {
            $table->string('no_bstb', 50)->nullable()->after('notes')->index();
        });
    }

    public function down(): void
    {
        Schema::table('production_reports', function (Blueprint $table) {
            $table->dropColumn('no_bstb');
        });
    }
};

