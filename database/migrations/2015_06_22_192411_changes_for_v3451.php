<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV3451
 */
class ChangesForV3451 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            'piggy_banks', function (Blueprint $table) {
            $table->smallInteger('reminder_skip')->unsigned();
            $table->boolean('remind_me');
        }
        );

    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'piggy_banks', function (Blueprint $table) {
            //$table->dropColumn('reminder_skip');
            $table->dropColumn('remind_me');
        }
        );
    }
}
