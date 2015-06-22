<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV3451
 */
class ChangesForV3451 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create(
            'reminders', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('user_id')->unsigned();
            $table->date('startdate');
            $table->date('enddate')->nullable();
            $table->boolean('active');
            $table->boolean('notnow')->default(0);
            $table->integer('remindersable_id')->unsigned()->nullable();
            $table->string('remindersable_type')->nullable();

            // connect reminders to users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        }
        );


        Schema::table(
            'piggy_banks', function (Blueprint $table) {
            //$table->smallInteger('reminder_skip')->unsigned();
            //$table->boolean('remind_me');
            $table->enum('reminder', ['day', 'week', 'quarter', 'month', 'year'])->nullable();
        }
        );

    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'piggy_banks', function (Blueprint $table) {
            //$table->dropColumn('reminder_skip');
            //$table->dropColumn('remind_me');
            $table->dropColumn('reminder');
        }
        );
        Schema::drop('reminders');
    }
}
