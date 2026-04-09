<?php

use App\Models\Restaurant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advice', function (Blueprint $table) {
            $table->unsignedBigInteger('restaurant_id')->nullable()->after('id');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
        });

        $restaurantId = Restaurant::query()->value('id');

        if ($restaurantId !== null) {
            DB::table('advice')
                ->whereNull('restaurant_id')
                ->update(['restaurant_id' => $restaurantId]);
        }
    }

    public function down(): void
    {
        Schema::table('advice', function (Blueprint $table) {
            $table->dropForeign(['restaurant_id']);
            $table->dropColumn('restaurant_id');
        });
    }
};
