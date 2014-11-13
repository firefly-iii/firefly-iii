<?php

use Illuminate\Database\Migrations\Migration;

class ExpandRemindersTable extends Migration
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
        Schema::table(
            'reminders', function ($table) {
                $table->string('title');
                $table->text('data');
            }
        );
    }

}
