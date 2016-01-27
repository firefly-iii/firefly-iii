<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV345
 *
 * @SuppressWarnings(PHPMD.ShortMethodName)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class ChangesForV345 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            'transaction_journals', function (Blueprint $table) {
            $table->dropColumn('tag_count');
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
        //
        Schema::table(
            'transaction_journals', function (Blueprint $table) {
            $table->smallInteger('tag_count', false, true)->default(0);
        }
        );
    }
}
