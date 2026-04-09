<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOfficialFieldsToAllergensTable extends Migration
{
    public function up()
    {
        Schema::table('allergens', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->boolean('is_official')->default(false)->after('slug');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_official');
        });
    }

    public function down()
    {
        Schema::table('allergens', function (Blueprint $table) {
            $table->dropColumn(['slug', 'is_official', 'sort_order']);
        });
    }
}
