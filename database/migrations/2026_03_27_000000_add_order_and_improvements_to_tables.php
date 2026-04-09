<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;
use App\Models\Product;

class AddOrderAndImprovementsToTables extends Migration
{
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('active');
            $table->string('icon')->nullable()->after('order');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('active');
            $table->boolean('featured')->default(false)->after('order');
            $table->string('offer_badge')->default('Oferta')->after('offer_price');
            $table->date('offer_start')->nullable()->after('offer_badge');
            $table->date('offer_end')->nullable()->after('offer_start');
        });

        // Asignar orden inicial basado en ID
        Category::orderBy('id')->each(function ($cat, $i) {
            $cat->update(['order' => $i]);
        });

        Product::orderBy('id')->each(function ($prod, $i) {
            $prod->update(['order' => $i]);
        });
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['order', 'icon']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['order', 'featured', 'offer_badge', 'offer_start', 'offer_end']);
        });
    }
}
