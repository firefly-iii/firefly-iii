<?php
declare(strict_types = 1);



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class CreatePiggybanksTable
 *
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
            $table->enum('rep_length', ['day', 'week', 'quarter', 'month', 'year'])->nullable();
            $table->smallInteger('rep_every')->unsigned();
            $table->smallInteger('rep_times')->unsigned()->nullable();
            $table->enum('reminder', ['day', 'week', 'quarter', 'month', 'year'])->nullable();
            $table->smallInteger('reminder_skip')->unsigned();
            $table->boolean('remind_me');
            $table->integer('order')->unsigned();

            // connect account to piggy bank.
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

            // for an account, the name must be unique.
            $table->unique(['account_id', 'name']);

        }
        );
    }

}
