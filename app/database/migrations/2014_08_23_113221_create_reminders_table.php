<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRemindersTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('reminders');
    }

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
                $table->string('class', 40);
                $table->integer('piggybank_id')->unsigned()->nullable();
                $table->integer('recurring_transaction_id')->unsigned()->nullable();
                $table->integer('user_id')->unsigned();
                $table->date('startdate');
                $table->date('enddate')->nullable();
                $table->boolean('active');


                // connect reminders to piggy banks.
                $table->foreign('piggybank_id')
                    ->references('id')->on('piggybanks')
                    ->onDelete('set null');

                // connect reminders to recurring transactions.
                $table->foreign('recurring_transaction_id')
                    ->references('id')->on('recurring_transactions')
                    ->onDelete('set null');


                // connect reminders to users
                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->onDelete('cascade');
            }
        );
    }

}
