<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->unsignedInteger('ai_credits')->default(0)->after('subdomain');
            $table->boolean('ai_demo_unlimited')->default(false)->after('ai_credits');
        });

        DB::table('restaurants')
            ->where('subdomain', 'carta')
            ->update([
                'ai_demo_unlimited' => true,
                'ai_credits' => 999999,
            ]);
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['ai_credits', 'ai_demo_unlimited']);
        });
    }
};
