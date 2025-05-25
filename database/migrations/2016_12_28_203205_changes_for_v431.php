<?php

/**
 * 2016_12_28_203205_changes_for_v431.php
 * Copyright (c) 2019 james@firefly-iii.org.
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
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class ChangesForV431.
 *
 * @codeCoverageIgnore
 */
class ChangesForV431 extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // reinstate "repeats" and "repeat_freq".
        if (!Schema::hasColumn('budget_limits', 'repeat_freq')) {
            try {
                Schema::table(
                    'budget_limits',
                    static function (Blueprint $table): void {
                        $table->string('repeat_freq', 30)->nullable();
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
        if (!Schema::hasColumn('budget_limits', 'repeats')) {
            try {
                Schema::table(
                    'budget_limits',
                    static function (Blueprint $table): void {
                        $table->boolean('repeats')->default(0);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
        // change field "start_date" to "startdate"
        if (Schema::hasColumn('budget_limits', 'start_date') && !Schema::hasColumn('budget_limits', 'startdate')) {
            try {
                Schema::table(
                    'budget_limits',
                    static function (Blueprint $table): void {
                        $table->renameColumn('start_date', 'startdate');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        // remove date field "end_date"
        if (Schema::hasColumn('budget_limits', 'end_date')) {
            try {
                Schema::table(
                    'budget_limits',
                    static function (Blueprint $table): void {
                        $table->dropColumn('end_date');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
        // remove decimal places
        if (Schema::hasColumn('transaction_currencies', 'decimal_places')) {
            try {
                Schema::table(
                    'transaction_currencies',
                    static function (Blueprint $table): void {
                        $table->dropColumn('decimal_places');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings("PHPMD.ShortMethodName")
     */
    public function up(): void
    {
        // add decimal places to "transaction currencies".
        if (!Schema::hasColumn('transaction_currencies', 'decimal_places')) {
            try {
                Schema::table(
                    'transaction_currencies',
                    static function (Blueprint $table): void {
                        $table->smallInteger('decimal_places', false, true)->default(2);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        // change field "startdate" to "start_date"
        if (Schema::hasColumn('budget_limits', 'startdate') && !Schema::hasColumn('budget_limits', 'start_date')) {
            try {
                Schema::table(
                    'budget_limits',
                    static function (Blueprint $table): void {
                        $table->renameColumn('startdate', 'start_date');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        // add date field "end_date" after "start_date"
        if (!Schema::hasColumn('budget_limits', 'end_date')) {
            try {
                Schema::table(
                    'budget_limits',
                    static function (Blueprint $table): void {
                        $table->date('end_date')->nullable()->after('start_date');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        // drop "repeats" and "repeat_freq".
        if (Schema::hasColumn('budget_limits', 'repeats')) {
            try {
                Schema::table(
                    'budget_limits',
                    static function (Blueprint $table): void {
                        $table->dropColumn('repeats');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
        if (Schema::hasColumn('budget_limits', 'repeat_freq')) {
            try {
                Schema::table(
                    'budget_limits',
                    static function (Blueprint $table): void {
                        $table->dropColumn('repeat_freq');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
    }
}
