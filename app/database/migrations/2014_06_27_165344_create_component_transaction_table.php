<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComponentTransactionTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('component_transaction', function(Blueprint $table)
		{
			$table->increments('id');
			$table->timestamps();
            $table->integer('component_id')->unsigned();
            $table->integer('transaction_id')->unsigned();

            // connect to components
            $table->foreign('component_id')
                ->references('id')->on('components')
                ->onDelete('cascade');

            // connect to transactions
            $table->foreign('transaction_id')
                ->references('id')->on('transactions')
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
		Schema::drop('component_transaction');
	}

}
