<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 * @SuppressWarnings("MethodLength") // I don't mind this in case of migrations.
 *
 * Class ChangesForV325
 */
class ChangesFor325 extends Migration
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
        //

        // delete an old index:
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            //$table->dropIndex('unique_ci_combo');
            $table->dropUnique('unique_ci_combi');
        });

    }

}
