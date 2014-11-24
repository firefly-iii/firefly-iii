<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class EventTableAdditions1 extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // remove some fields:
        Schema::table(
            'reminders', function (Blueprint $table) {
                $table->boolean('notnow');
                $table->integer('remindersable_id')->unsigned()->nullable();
                $table->string('remindersable_type')->nullable();
            }
        );
    }

}
