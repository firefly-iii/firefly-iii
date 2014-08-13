<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePiggyInstance extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('piggybank_repetitions', function(Blueprint $table)
		{
			$table->increments('id');
			$table->timestamps();
            $table->integer('piggybank_id')->unsigned();
            $table->date('targetdate')->nullable();
            $table->date('startdate')->nullable();
            $table->decimal('currentamount',10,2);

            // connect instance to piggybank.
            $table->foreign('piggybank_id')
                ->references('id')->on('piggybanks')
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
		Schema::drop('piggybank_repetitions');
	}

}
