<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Restaura el pictograma oficial del alérgeno «Crustáceos» si se quitó la imagen desde el panel.
     */
    public function up(): void
    {
        DB::table('allergens')
            ->where('slug', 'crustaceos')
            ->update(['image' => 'allergens/official/crustaceos.png']);
    }

    public function down(): void
    {
        //
    }
};
