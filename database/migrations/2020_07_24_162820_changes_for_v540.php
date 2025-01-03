<?php

/*
 * 2020_07_24_162820_changes_for_v540.php
 * Copyright (c) 2021 james@firefly-iii.org
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
 * Class ChangesForV540
 *
 * @codeCoverageIgnore
 */
class ChangesForV540 extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('oauth_clients', 'provider')) {
            try {
                Schema::table(
                    'oauth_clients',
                    static function (Blueprint $table): void {
                        $table->dropColumn('provider');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        if (Schema::hasColumn('accounts', 'order')) {
            try {
                Schema::table(
                    'accounts',
                    static function (Blueprint $table): void {
                        $table->dropColumn('order');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
        // in two steps for sqlite
        if (Schema::hasColumn('bills', 'end_date')) {
            try {
                Schema::table(
                    'bills',
                    static function (Blueprint $table): void {
                        $table->dropColumn('end_date');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
        if (Schema::hasColumn('bills', 'extension_date')) {
            try {
                Schema::table(
                    'bills',
                    static function (Blueprint $table): void {
                        $table->dropColumn('extension_date');
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
        if (!Schema::hasColumn('accounts', 'order')) {
            try {
                Schema::table(
                    'accounts',
                    static function (Blueprint $table): void {
                        $table->integer('order', false, true)->default(0);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        if (!Schema::hasColumn('oauth_clients', 'provider')) {
            try {
                Schema::table(
                    'oauth_clients',
                    static function (Blueprint $table): void {
                        $table->string('provider')->nullable();
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        if (!Schema::hasColumn('bills', 'end_date') && !Schema::hasColumn('bills', 'extension_date')) {
            try {
                Schema::table(
                    'bills',
                    static function (Blueprint $table): void {
                        $table->date('end_date')->nullable()->after('date');
                        $table->date('extension_date')->nullable()->after('end_date');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        // make column nullable:
        try {
            Schema::table(
                'oauth_clients',
                static function (Blueprint $table): void {
                    $table->string('secret', 100)->nullable()->change();
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }
    }
}
