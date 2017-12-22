<?php
/**
 * 2016_12_28_203205_changes_for_v431.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
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
