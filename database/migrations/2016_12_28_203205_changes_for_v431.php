<?php
/**
 * 2016_12_28_203205_changes_for_v431.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
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
    public function down(): void
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

        // change field "start_date" to "startdate"
        Schema::table(
            'budget_limits',
            function (Blueprint $table) {
                $table->renameColumn('start_date', 'startdate');
            }
        );

        // remove date field "end_date"
        Schema::table(
            'budget_limits',
            function (Blueprint $table) {
                $table->dropColumn('end_date');
            }
        );
        // remove decimal places
        Schema::table(
            'transaction_currencies',
            function (Blueprint $table) {
                $table->dropColumn('decimal_places');
            }
        );
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(): void
    {
        // add decimal places to "transaction currencies".
        Schema::table(
            'transaction_currencies',
            static function (Blueprint $table) {
                $table->smallInteger('decimal_places', false, true)->default(2);
            }
        );

        // change field "startdate" to "start_date"
        Schema::table(
            'budget_limits',
            static function (Blueprint $table) {
                $table->renameColumn('startdate', 'start_date');
            }
        );

        // add date field "end_date" after "start_date"
        Schema::table(
            'budget_limits',
            static function (Blueprint $table) {
                $table->date('end_date')->nullable()->after('start_date');
            }
        );

        // drop "repeats" and "repeat_freq".
        Schema::table(
            'budget_limits',
            static function (Blueprint $table) {
                $table->dropColumn('repeats');
            }
        );
        Schema::table(
            'budget_limits',
            static function (Blueprint $table) {
                $table->dropColumn('repeat_freq');
            }
        );
    }
}
