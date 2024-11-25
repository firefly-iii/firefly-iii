<?php

/**
 * 2019_03_22_183214_changes_for_v480.php
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
 * Class ChangesForV480.
 *
 * @codeCoverageIgnore
 */
class ChangesForV480 extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // remove group ID
        if (Schema::hasColumn('transaction_journals', 'transaction_group_id')) {
            try {
                Schema::table(
                    'transaction_journals',
                    static function (Blueprint $table): void {
                        // drop transaction_group_id + foreign key.
                        // cannot drop foreign keys in SQLite:
                        if ('sqlite' !== config('database.default')) {
                            try {
                                $table->dropForeign('transaction_journals_transaction_group_id_foreign');
                            } catch (QueryException $e) {
                                app('log')->error(sprintf('Could not drop foreign ID: %s', $e->getMessage()));
                                app('log')->error('If the foreign ID does not exist (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
                            }
                        }

                        try {
                            $table->dropColumn('transaction_group_id');
                        } catch (QueryException $e) {
                            app('log')->error(sprintf('Could not drop column: %s', $e->getMessage()));
                            app('log')->error('If the column does not exist, this is not an problem. Otherwise, please open a GitHub discussion.');
                        }
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        // remove 'stop processing' column
        if (Schema::hasColumn('rule_groups', 'stop_processing')) {
            try {
                Schema::table(
                    'rule_groups',
                    static function (Blueprint $table): void {
                        try {
                            $table->dropColumn('stop_processing');
                        } catch (QueryException $e) {
                            app('log')->error(sprintf('Could not drop column: %s', $e->getMessage()));
                            app('log')->error('If the column does not exist, this is not an problem. Otherwise, please open a GitHub discussion.');
                        }
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        // remove 'mfa_secret' column
        if (Schema::hasColumn('users', 'mfa_secret')) {
            try {
                Schema::table(
                    'users',
                    static function (Blueprint $table): void {
                        try {
                            $table->dropColumn('mfa_secret');
                        } catch (QueryException $e) {
                            app('log')->error(sprintf('Could not drop column: %s', $e->getMessage()));
                            app('log')->error('If the column does not exist, this is not an problem. Otherwise, please open a GitHub discussion.');
                        }
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
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up(): void
    {
        // add currency_id
        if (!Schema::hasColumn('transaction_journals', 'transaction_group_id')) {
            try {
                Schema::table(
                    'transaction_journals',
                    static function (Blueprint $table): void {
                        $table->integer('transaction_currency_id', false, true)->nullable()->change();

                        // add column "group_id" after "transaction_type_id"
                        $table->integer('transaction_group_id', false, true)
                            ->nullable()->default(null)->after('transaction_type_id')
                        ;

                        // add foreign key for "transaction_group_id"
                        try {
                            $table->foreign('transaction_group_id')->references('id')->on('transaction_groups')->onDelete('cascade');
                        } catch (QueryException $e) {
                            app('log')->error(sprintf('Could not create foreign index: %s', $e->getMessage()));
                            app('log')->error(
                                'If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.'
                            );
                        }
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        // add 'stop processing' column
        if (!Schema::hasColumn('rule_groups', 'stop_processing')) {
            try {
                Schema::table(
                    'rule_groups',
                    static function (Blueprint $table): void {
                        $table->boolean('stop_processing')->default(false);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        // add 'mfa_secret' column
        if (!Schema::hasColumn('users', 'mfa_secret')) {
            try {
                Schema::table(
                    'users',
                    static function (Blueprint $table): void {
                        $table->string('mfa_secret', 50)->nullable();
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
    }
}
