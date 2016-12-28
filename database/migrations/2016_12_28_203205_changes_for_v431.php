<?php
/**
 * 2016_12_28_203205_changes_for_v431.php
 * Copyright (c) 2016 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV431
 */
class ChangesForV431 extends Migration
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
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up()
    {
        Schema::table(
            'transaction_currencies', function (Blueprint $table) {
            $table->smallInteger('decimal_places',false, true)->default(2);
        }
        );
    }
}
