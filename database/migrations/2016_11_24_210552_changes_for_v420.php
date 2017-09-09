<?php

/**
 * 2016_11_24_210552_changes_for_v420.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV420
 */
class ChangesForV420 extends Migration
{
    /**
     * Reverse the migrations.
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
            'journal_meta', function (Blueprint $table) {
            $table->softDeletes();
        }
        );
    }
}
