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
        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->integer('user_id');
            $table->string('user_name');
            $table->string('user_email');
            $table->string('user_phone');
            $table->string('coupon_code')->nullable();
            $table->string('coupon_amount')->default(0);
            $table->string('shipping_method')->nullable();
            $table->string('shipping_charge')->default(0);
            $table->string('payment_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('sub_total')->default(0);
            $table->string('grand_total')->default(0);
            $table->string('shipping_user_name')->nullable();
            $table->string('shipping_email')->nullable();
            $table->string('shipping_phone')->nullable();
            $table->string('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_zip')->nullable();
            $table->string('shipping_flat')->nullable();
            $table->string('shipping_area')->nullable();
            $table->string('shipping_landmark')->nullable();
            $table->string('shipping_address_type')->nullable();
            $table->string('billing_name')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_zip')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_phone')->nullable();
            $table->integer('order_status');
            $table->integer('payment_status')->nullable();;
            $table->string('order_date')->nullable();
            $table->string('delivery_date')->nullable();
            $table->string('cancel_date')->nullable();
            $table->string('cancel_note')->nullable();
            $table->string('prefered_time')->nullable();
            $table->string('invoice_file')->nullable();
            $table->string('vendor_invoice_file')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('vendor_invoice_no')->nullable();
            $table->integer('is_same_as_shipping_address')->default(0)->comment('0=>No,1=>Yes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order');
    }
};
