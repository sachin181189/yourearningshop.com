<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyProfileTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_profile', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('logo');
            $table->string('address');
            $table->string('conatct_no');
            $table->string('email');
            $table->string('phone');
            $table->string('whatsapp_no');
            $table->string('facebook_link');
            $table->string('twitter_link');
            $table->string('linkdin_link');
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
        Schema::dropIfExists('company_profile');
    }
}
