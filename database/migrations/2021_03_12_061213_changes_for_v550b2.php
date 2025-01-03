<?php

/*
 * 2021_03_12_061213_changes_for_v550b2.php
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
 * Class ChangesForV550b2
 */
class ChangesForV550b2 extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('recurrences_transactions', 'transaction_type_id')) {
            try {
                Schema::table(
                    'recurrences_transactions',
                    static function (Blueprint $table): void {
                        if ('sqlite' !== config('database.default')) {
                            $table->dropForeign('type_foreign');
                        }
                        if (Schema::hasColumn('recurrences_transactions', 'transaction_type_id')) {
                            $table->dropColumn('transaction_type_id');
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
     * @SuppressWarnings("PHPMD.ShortMethodName")
     */
    public function up(): void
    {
        // expand recurrence transaction table
        if (!Schema::hasColumn('recurrences_transactions', 'transaction_type_id')) {
            try {
                Schema::table(
                    'recurrences_transactions',
                    static function (Blueprint $table): void {
                        if (!Schema::hasColumn('recurrences_transactions', 'transaction_type_id')) {
                            $table->integer('transaction_type_id', false, true)->nullable()->after('transaction_currency_id');
                            $table->foreign('transaction_type_id', 'type_foreign')->references('id')->on('transaction_types')->onDelete('set null');
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
