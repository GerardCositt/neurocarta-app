<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixSettingsUniqueKey extends Migration
{
    public function up()
    {
        // Añadir restaurant_id si no existe aún
        if (! Schema::hasColumn('settings', 'restaurant_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->unsignedBigInteger('restaurant_id')->nullable()->after('id');
            });
        }

        // Eliminar el índice único antiguo (solo 'key') si existe
        try {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropUnique(['key']);
            });
        } catch (\Exception $e) {
            // Ya no existe o tiene otro nombre, continuar
        }

        // Crear índice compuesto (restaurant_id, key) si no existe
        try {
            Schema::table('settings', function (Blueprint $table) {
                $table->unique(['restaurant_id', 'key']);
            });
        } catch (\Exception $e) {
            // Ya existe, continuar
        }
    }

    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            try { $table->dropUnique(['restaurant_id', 'key']); } catch (\Exception $e) {}
            try { $table->unique('key'); } catch (\Exception $e) {}
        });
    }
}
