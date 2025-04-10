<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_product', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->integer('qty');
            $table->string('product_name')->nullable();
            $table->string('product_price')->nullable();
            $table->string('offer_price')->nullable();
            $table->string('variant_value1')->nullable();
            $table->string('variant_value2')->nullable();
            $table->integer('return_exchange_policy_type');
            $table->integer('return_exchange_days');
            $table->string('product_image')->nullable();
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->string('brand')->nullable();
            $table->string('order_id');
            $table->integer('assigned_driver')->default('0');
            $table->string('driver_name')->nullable();
            $table->string('driver_email')->nullable();
            $table->string('driver_phone')->nullable();
            $table->string('alternate_mobile')->nullable();
            $table->string('driver_image')->nullable();
            $table->integer('vendor_id');
            $table->string('vendor_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('vendor_alternate_mobile')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('service_description')->nullable();
            $table->string('gst_no')->nullable();
            $table->integer('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_product');
    }
};
