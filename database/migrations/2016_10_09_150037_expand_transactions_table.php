<?php

/**
 * 2016_10_09_150037_expand_transactions_table.php
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
 * Class ExpandTransactionsTable.
 *
 * @codeCoverageIgnore
 */
class ExpandTransactionsTable extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'identifier')) {
            try {
                Schema::table(
                    'transactions',
                    static function (Blueprint $table): void {
                        $table->dropColumn('identifier');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not drop column "identifier": %s', $e->getMessage()));
                app('log')->error('If the column does not exist, this is not an problem. Otherwise, please open a GitHub discussion.');
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
        if (!Schema::hasColumn('transactions', 'identifier')) {
            try {
                Schema::table(
                    'transactions',
                    static function (Blueprint $table): void {
                        $table->smallInteger('identifier', false, true)->default(0);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
    }
}
