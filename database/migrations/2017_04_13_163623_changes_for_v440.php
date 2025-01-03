<?php

/**
 * 2017_04_13_163623_changes_for_v440.php
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
 * Class ChangesForV440.
 *
 * @codeCoverageIgnore
 */
class ChangesForV440 extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_exchange_rates');

        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table): void {
                    if (Schema::hasColumn('transactions', 'transaction_currency_id')) {
                        // cannot drop foreign keys in SQLite:
                        if ('sqlite' !== config('database.default')) {
                            $table->dropForeign('transactions_transaction_currency_id_foreign');
                        }
                        $table->dropColumn('transaction_currency_id');
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings("PHPMD.ShortMethodName")
     */
    public function up(): void
    {
        if (!Schema::hasTable('currency_exchange_rates')) {
            try {
                Schema::create(
                    'currency_exchange_rates',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->integer('user_id', false, true);
                        $table->integer('from_currency_id', false, true);
                        $table->integer('to_currency_id', false, true);
                        $table->date('date');
                        $table->decimal('rate', 32, 12);
                        $table->decimal('user_rate', 32, 12)->nullable();

                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                        $table->foreign('from_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
                        $table->foreign('to_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "notifications": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }
        if (!Schema::hasColumn('transactions', 'transaction_currency_id')) {
            try {
                Schema::table(
                    'transactions',
                    static function (Blueprint $table): void {
                        if (!Schema::hasColumn('transactions', 'transaction_currency_id')) {
                            $table->integer('transaction_currency_id', false, true)->after('description')->nullable();
                            $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('set null');
                        }
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
    }
}
