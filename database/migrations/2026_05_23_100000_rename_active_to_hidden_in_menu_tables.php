<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'categories', 'pairings', 'allergens'] as $table) {
            DB::statement("ALTER TABLE {$table} RENAME COLUMN active TO hidden");
        }
    }

    public function down(): void
    {
        foreach (['products', 'categories', 'pairings', 'allergens'] as $table) {
            DB::statement("ALTER TABLE {$table} RENAME COLUMN hidden TO active");
        }
    }
};
