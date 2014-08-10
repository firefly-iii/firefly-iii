<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateTransactionTypesTable
 *
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class CreateTransactionTypesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('transaction_types', function(Blueprint $table)
		{
			$table->increments('id');
			$table->timestamps();
            $table->string('type',50);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('transaction_types');
	}

}
