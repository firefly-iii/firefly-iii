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
            $table->string('class',20);

            // connect components to users
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->unique(['user_id','class','name']);
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
