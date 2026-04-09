<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('offer')->default(false);
            $table->boolean('active')->default(false);
            $table->string('price')->nullable();
            $table->string('offer_price')->nullable();
            $table->string('photo')->nullable();
            $table->string('aller')->nullable();
            $table->foreignId('category_id');
            $table->bigInteger('pairing_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
