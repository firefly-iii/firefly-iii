<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountMeta extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create(
            'account_meta', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->integer('account_id')->unsigned();
                $table->string('name');
                $table->text('data');


            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::drop('account_meta');
    }

}
