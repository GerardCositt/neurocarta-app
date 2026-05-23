<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('key_hash', 64);
            $table->string('key_prefix', 20);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index('key_hash');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('external_sku', 100)->nullable()->after('id');
            $table->index(['restaurant_id', 'external_sku']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('external_sku', 100)->nullable()->after('id');
            $table->index(['restaurant_id', 'external_sku']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['restaurant_id', 'external_sku']);
            $table->dropColumn('external_sku');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['restaurant_id', 'external_sku']);
            $table->dropColumn('external_sku');
        });

        Schema::dropIfExists('restaurant_api_keys');
    }
};
