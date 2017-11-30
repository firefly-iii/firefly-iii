<?php

/**
 * 2016_12_28_203205_changes_for_v431.php
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
 * Class ChangesForV431
 */
class ChangesForV431 extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // reinstate "repeats" and "repeat_freq".
        Schema::table(
            'budget_limits',
            function (Blueprint $table) {
                $table->string('repeat_freq', 30)->nullable();
            }
        );
        Schema::table(
            'budget_limits',
            function (Blueprint $table) {
                $table->boolean('repeats')->default(0);
            }
        );

        // remove date field "end_date"
        Schema::table(
            'budget_limits',
            function (Blueprint $table) {
                $table->dropColumn('end_date');
            }
        );
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up()
    {
        // add decimal places to "transaction currencies".
        Schema::table(
            'transaction_currencies',
            function (Blueprint $table) {
                $table->smallInteger('decimal_places', false, true)->default(2);
            }
        );

        // change field "startdate" to "start_date"
        Schema::table(
            'budget_limits',
            function (Blueprint $table) {
                $table->renameColumn('startdate', 'start_date');
            }
        );

        // add date field "end_date" after "start_date"
        Schema::table(
            'budget_limits',
            function (Blueprint $table) {
                $table->date('end_date')->nullable()->after('start_date');
            }
        );

        // drop "repeats" and "repeat_freq".
        Schema::table(
            'budget_limits',
            function (Blueprint $table) {
                $table->dropColumn('repeats');
            }
        );
        Schema::table(
            'budget_limits',
            function (Blueprint $table) {
                $table->dropColumn('repeat_freq');
            }
        );
    }
}
