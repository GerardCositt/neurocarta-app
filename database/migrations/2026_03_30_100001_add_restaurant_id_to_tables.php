<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRestaurantIdToTables extends Migration
{
    public function up()
    {
        // `settings` ya tiene `restaurant_id` en create_settings_table (2026_03_30_072525).
        $tables = ['categories', 'products', 'pairings', 'orders'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('restaurant_id')->nullable()->after('id');
                $blueprint->foreign('restaurant_id')->references('id')->on('restaurants')->nullOnDelete();
            });
        }

        Schema::table('settings', function (Blueprint $blueprint) {
            $blueprint->foreign('restaurant_id')->references('id')->on('restaurants')->nullOnDelete();
        });
    }

    public function down()
    {
        $tables = ['categories', 'products', 'pairings', 'orders'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['restaurant_id']);
                $blueprint->dropColumn('restaurant_id');
            });
        }

        Schema::table('settings', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['restaurant_id']);
        });
    }
}
