<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paso intermedio de la cadena hacia starts_at/ends_at (2026_04_06_150000).
 * No eliminar: las bases ya migradas dependen del orden 120000 → 140000 → 150000.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advice', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('status');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('advice', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
