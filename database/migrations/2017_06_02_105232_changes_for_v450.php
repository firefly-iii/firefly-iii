<?php

/**
 * 2017_06_02_105232_changes_for_v450.php
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

/**
 * Class ChangesForV450.
 *
 * @codeCoverageIgnore
 */
class ChangesForV450 extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // split up for sqlite compatibility
        if (Schema::hasColumn('transactions', 'foreign_amount')) {
            try {
                Schema::table(
                    'transactions',
                    static function (Blueprint $table): void {
                        $table->dropColumn('foreign_amount');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table): void {
                    // cannot drop foreign keys in SQLite:
                    if ('sqlite' !== config('database.default')) {
                        $table->dropForeign('transactions_foreign_currency_id_foreign');
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }
        if (Schema::hasColumn('transactions', 'foreign_currency_id')) {
            try {
                Schema::table(
                    'transactions',
                    static function (Blueprint $table): void {
                        $table->dropColumn('foreign_currency_id');
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
        // add "foreign_amount" to transactions
        if (!Schema::hasColumn('transactions', 'foreign_amount')) {
            try {
                Schema::table(
                    'transactions',
                    static function (Blueprint $table): void {
                        $table->decimal('foreign_amount', 32, 12)->nullable()->after('amount');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        // add foreign transaction currency id to transactions (is nullable):
        if (!Schema::hasColumn('transactions', 'foreign_currency_id')) {
            try {
                Schema::table(
                    'transactions',
                    static function (Blueprint $table): void {
                        $table->integer('foreign_currency_id', false, true)->default(null)->after('foreign_amount')->nullable();
                        $table->foreign('foreign_currency_id')->references('id')->on('transaction_currencies')->onDelete('set null');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
    }
}
