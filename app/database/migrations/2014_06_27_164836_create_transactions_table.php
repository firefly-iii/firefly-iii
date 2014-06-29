<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('transactions', function(Blueprint $table)
		{
			$table->increments('id');
			$table->timestamps();
            $table->integer('account_id')->integer();
            $table->integer('transaction_journal_id')->integer()->unsigned();
            $table->string('description',255)->nullable();
            $table->decimal('amount',10,2);

            // connect transactions to transaction journals
            $table->foreign('transaction_journal_id')
                ->references('id')->on('transaction_journals')
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
		Schema::drop('transactions');
	}

}
