<?php


/*
 * 2024_11_05_062108_add_date_tz_columns.php
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

return new class () extends Migration {
    private array $tables;

    public function __construct()
    {
        $this->tables = [
            'account_balances'        => ['date'], // done
            'available_budgets'       => ['start_date', 'end_date'], // done
            'bills'                   => ['date', 'end_date', 'extension_date'], // done
            'budget_limits'           => ['start_date', 'end_date'], // done
            'currency_exchange_rates' => ['date'], // done
            'invited_users'           => ['expires'],
            'piggy_bank_events'       => ['date'],
            'piggy_bank_repetitions'  => ['startdate', 'targetdate'],
            'piggy_banks'             => ['startdate', 'targetdate'], // done
            'recurrences'             => ['first_date', 'repeat_until', 'latest_date'],
            'tags'                    => ['date'],
            'transaction_journals'    => ['date'],
        ];
    }

    /**
     * Run the migrations.
     * TODO journal_meta, all date fields?
     */
    public function up(): void
    {
        foreach ($this->tables as $table => $columns) {
            foreach ($columns as $column) {
                $newColumn = sprintf('%s_tz', $column);
                if (Schema::hasColumn($table, $column) && !Schema::hasColumn($table, $newColumn)) {
                    try {
                        Schema::table(
                            $table,
                            static function (Blueprint $table) use ($column, $newColumn): void {
                                $table->string($newColumn, 50)->nullable()->after($column);
                            }
                        );
                    } catch (QueryException $e) {
                        app('log')->error(sprintf('Could not add column "%s" to table "%s" query: %s', $newColumn, $table, $e->getMessage()));
                        app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
