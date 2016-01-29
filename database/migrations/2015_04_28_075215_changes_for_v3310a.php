<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class ChangesForV3310a
 */
class ChangesForV3310a extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'transaction_groups', function (Blueprint $table) {

            // new column, relation.
            $table->string('relation', 50)->nullable();
        }
        );
        // make new column "relation"

    }

}
