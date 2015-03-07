<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class ChangesForV332
 */
class ChangesForV332 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{

        Schema::table(
            'accounts', function (Blueprint $table) {
            $table->boolean('encrypted');

        }
        );

        Schema::table(
            'reminders', function (Blueprint $table) {
            $table->text('metadata');

        }
        );


    }

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		//
	}

}
