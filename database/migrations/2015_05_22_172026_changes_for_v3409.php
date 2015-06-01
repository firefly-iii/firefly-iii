<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class ChangesForV3409
 */
class ChangesForV3409 extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            'preferences', function (Blueprint $table) {
            $table->dropColumn('name_encrypted');
            $table->dropColumn('data_encrypted');
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

        // encrypt preference name (add field)
        // encrypt preference data (add field)
        Schema::table(
            'preferences', function (Blueprint $table) {
            $table->text('name_encrypted')->nullable()->after('name');
            $table->text('data_encrypted')->nullable()->after('data');
        }
        );

    }

}
