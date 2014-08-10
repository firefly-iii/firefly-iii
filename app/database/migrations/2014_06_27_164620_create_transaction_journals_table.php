<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateTransactionJournalsTable
 *
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class CreateTransactionJournalsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('transaction_journals', function(Blueprint $table)
		{
			$table->increments('id');
			$table->timestamps();
            $table->integer('user_id')->unsigned();
            $table->integer('transaction_type_id')->unsigned();
            $table->integer('transaction_currency_id')->unsigned();
            $table->string('description',255)->nullable();
            $table->boolean('completed');
            $table->date('date');

            // connect transaction journals to transaction types
            $table->foreign('transaction_type_id')
                ->references('id')->on('transaction_types')
                ->onDelete('cascade');

            // connect transaction journals to transaction currencies
            $table->foreign('transaction_currency_id')
                ->references('id')->on('transaction_currencies')
                ->onDelete('cascade');

            // connect users
            $table->foreign('user_id')
                ->references('id')->on('users')
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
		Schema::drop('transaction_journals');
	}

}
