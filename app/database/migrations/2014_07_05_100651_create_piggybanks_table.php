<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePiggybanksTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'piggybanks', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->integer('account_id')->unsigned();
                $table->date('targetdate')->nullable();
                $table->string('name', 500);
                $table->decimal('amount', 10, 2);
                $table->decimal('target', 10, 2)->nullable();
                $table->integer('order')->unsigned();

                // connect account to piggybank.
                $table->foreign('account_id')
                    ->references('id')->on('accounts')
                    ->onDelete('cascade');

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
        Schema::drop('piggybanks');
    }

}
