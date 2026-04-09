<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable');          // translatable_type + translatable_id
            $table->string('locale', 10);            // es, en, fr, de, …
            $table->string('key', 80);               // name, description, title, advice, …
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(
                ['translatable_type', 'translatable_id', 'locale', 'key'],
                'translations_unique'
            );
            $table->index(['translatable_type', 'translatable_id', 'locale'], 'translations_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
