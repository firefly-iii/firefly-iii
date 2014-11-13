<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateRecurringTransactionsTable
 *
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class CreateRecurringTransactionsTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('recurring_transactions');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'recurring_transactions', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->integer('user_id')->unsigned();
                $table->string('name', 50);
                $table->string('match', 255);
                $table->decimal('amount_min', 10, 2);
                $table->decimal('amount_max', 10, 2);
                $table->date('date');
                $table->boolean('active');

                $table->boolean('automatch');
                $table->enum('repeat_freq', ['daily', 'weekly', 'monthly', 'quarterly', 'half-year', 'yearly']);
                $table->smallInteger('skip')->unsigned();

                $table->unique(['user_id', 'name']);


            }
        );
    }

}
