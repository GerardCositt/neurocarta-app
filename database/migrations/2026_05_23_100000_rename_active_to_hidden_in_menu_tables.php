<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('active', 'hidden');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->renameColumn('active', 'hidden');
        });

        Schema::table('pairings', function (Blueprint $table) {
            $table->renameColumn('active', 'hidden');
        });

        Schema::table('allergens', function (Blueprint $table) {
            $table->renameColumn('active', 'hidden');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('hidden', 'active');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->renameColumn('hidden', 'active');
        });

        Schema::table('pairings', function (Blueprint $table) {
            $table->renameColumn('hidden', 'active');
        });

        Schema::table('allergens', function (Blueprint $table) {
            $table->renameColumn('hidden', 'active');
        });
    }
};
