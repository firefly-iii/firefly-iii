<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComponentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('components', function(Blueprint $table)
		{
			$table->increments('id');
			$table->timestamps();
            $table->string('name',50);
            $table->integer('user_id')->unsigned();
            $table->integer('component_type_id')->unsigned();

            // connect components to users
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            // connect components to component types
            $table->foreign('component_type_id')
                ->references('id')->on('component_types')
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
		Schema::drop('components');
	}

}
