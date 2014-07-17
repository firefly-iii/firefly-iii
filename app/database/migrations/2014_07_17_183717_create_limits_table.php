<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLimitsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('limits', function(Blueprint $table)
		{
			$table->increments('id');
			$table->timestamps();
            $table->integer('component_id')->unsigned();
            $table->date('startdate');
            $table->date('enddate');
            $table->decimal('amount',10,2);

            // connect component
            $table->foreign('component_id')
                ->references('id')->on('components')
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
		Schema::drop('limits');
	}

}
