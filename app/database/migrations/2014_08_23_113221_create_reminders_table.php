<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRemindersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'reminders', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->string('class', 30);
                $table->integer('piggybank_id')->unsigned()->nullable();
                $table->integer('user_id')->unsigned();
                $table->date('startdate');
                $table->date('enddate');
                $table->boolean('active');


                // connect reminders to piggy banks.
                $table->foreign('piggybank_id')
                    ->references('id')->on('piggybanks')
                    ->onDelete('set null');

                // connect reminders to users
                $table->foreign('user_id')
                    ->references('id')->on('users')
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
        Schema::drop('reminders');
    }

}
