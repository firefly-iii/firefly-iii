<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreatePiggybanksTable
 *
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class CreatePiggybanksTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('piggybanks');
    }

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
                $table->string('name', 100);
                $table->decimal('targetamount', 10, 2);
                $table->date('startdate')->nullable();
                $table->date('targetdate')->nullable();
                $table->boolean('repeats');
                $table->enum('rep_length', ['day', 'week', 'month', 'year'])->nullable();
                $table->smallInteger('rep_every')->unsigned();
                $table->smallInteger('rep_times')->unsigned()->nullable();
                $table->enum('reminder', ['day', 'week', 'month', 'year'])->nullable();
                $table->smallInteger('reminder_skip')->unsigned();
                $table->boolean('remind_me');
                $table->integer('order')->unsigned();

                // connect account to piggybank.
                $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
                $table->unique(['account_id', 'name']);

            }
        );
    }

}
