<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->integer('vendor_id');
            $table->integer('category_id');
            $table->integer('subcategory_id');
            $table->integer('sub_subcategory_id');
            $table->string('product_description');
            $table->integer('product_type');
            $table->integer('stock');
            $table->float('price');
            $table->float('offer_price');
            $table->string('color');
            $table->string('size');
            $table->integer('unit');
            $table->tinyInteger('best_deal');
            $table->tinyInteger('hot_deal');
            $table->tinyInteger('is_best_seller');
            $table->tinyInteger('is_todays_deal');
            $table->tinyInteger('status')->default('1');
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
        Schema::dropIfExists('product');
    }
}
