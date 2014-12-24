<?php

use Illuminate\Database\Migrations\Migration;

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
            'sessions', function ($t) {
                $t->string('id')->unique();
                $t->text('payload');
                $t->integer('last_activity');
            }
        );
    }

}
