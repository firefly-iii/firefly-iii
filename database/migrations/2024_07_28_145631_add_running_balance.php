<?php


/*
 * 2024_07_28_145631_add_running_balance.php
 * Copyright (c) 2025 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table): void {
                    if (!Schema::hasColumn('transactions', 'balance_before')) {
                        $table->decimal('balance_before', 32, 12)->nullable()->after('amount');
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }

        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table): void {
                    if (!Schema::hasColumn('transactions', 'balance_after')) {
                        $table->decimal('balance_after', 32, 12)->nullable()->after('balance_before');
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }

        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table): void {
                    if (!Schema::hasColumn('transactions', 'balance_dirty')) {
                        $table->boolean('balance_dirty')->default(true)->after('balance_after');
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table): void {
                    if (Schema::hasColumn('transactions', 'balance_before')) {
                        $table->dropColumn('balance_before');
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }

        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table): void {
                    if (Schema::hasColumn('transactions', 'balance_after')) {
                        $table->dropColumn('balance_after');
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }

        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table): void {
                    if (Schema::hasColumn('transactions', 'balance_dirty')) {
                        $table->dropColumn('balance_dirty');
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }
    }
};
