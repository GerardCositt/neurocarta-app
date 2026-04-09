<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columna puente retirada en 2026_04_06_150000; conservada en el historial para entornos que ya ejecutaron up().
 */
class AddForceActiveToAdviceTable extends Migration
{
    public function up(): void
    {
        Schema::table('advice', function (Blueprint $table) {
            $table->boolean('force_active')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('advice', function (Blueprint $table) {
            $table->dropColumn('force_active');
        });
    }
}
