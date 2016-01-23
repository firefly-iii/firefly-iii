<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Add2FactorAuthenticationSupport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add 2fa fields to users:        
        Schema::table(
            'users', function (Blueprint $table) {
            $table->unsignedTinyInteger('is_2fa_enabled')->default(0);
            $table->string('secret_key', 255);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
