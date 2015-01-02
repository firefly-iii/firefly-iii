<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateSessionTable
 *
 */
class CreateSessionTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sessions');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'sessions', function (Blueprint $table) {
            $table->string('id')->unique();
            $table->text('payload');
            $table->integer('last_activity');
        }
        );
    }

}
