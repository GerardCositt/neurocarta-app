<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Restaura el pictograma oficial si se quitó la imagen del alérgeno «Cereales con gluten» desde el panel.
     */
    public function up(): void
    {
        DB::table('allergens')
            ->where('slug', 'cereales-gluten')
            ->update(['image' => 'allergens/official/cereales_gluten.png']);
    }

    public function down(): void
    {
        //
    }
};
