<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateLimitsTable
 *
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
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
            $table->decimal('amount',10,2);
            $table->boolean('repeats');
            $table->enum('repeat_freq', ['daily', 'weekly','monthly','quarterly','half-year','yearly']);

            $table->unique(['component_id','startdate','repeat_freq']);

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
