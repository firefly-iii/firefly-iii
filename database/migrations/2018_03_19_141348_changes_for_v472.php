<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV472
 */
class ChangesForV472 extends Migration
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
            'attachments',
            function (Blueprint $table) {
                $table->dropColumn('notes');
            }
        );

        Schema::table(
            'budgets',
            function (Blueprint $table) {
                $table->mediumInteger('order', false, true)->default(0);
            }
        );
    }
}
