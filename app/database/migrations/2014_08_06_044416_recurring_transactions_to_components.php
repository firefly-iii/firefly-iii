<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RecurringTransactionsToComponents extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('component_recurring_transaction', function(Blueprint $table)
		{
			$table->increments('id');
            $table->integer('component_id')->unsigned();
            $table->integer('recurring_transaction_id')->unsigned();
            $table->boolean('optional');

            // link components with component_id
            $table->foreign('component_id')
                ->references('id')->on('components')
                ->onDelete('cascade');

            // link transaction journals with transaction_journal_id
            $table->foreign('recurring_transaction_id')
                ->references('id')->on('recurring_transactions')
                ->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('component_recurring_transaction');
	}

}
