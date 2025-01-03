<?php


/*
 * 2024_12_19_061003_add_native_amount_column.php
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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private array $tables
        = [
            // !!! this array is also in PreferencesEventHandler + RecalculateNativeAmountsCommand
            'accounts'           => ['native_virtual_balance'], // works.
            'account_piggy_bank' => ['native_current_amount'], // works
            'auto_budgets'       => ['native_amount'], // works
            'available_budgets'  => ['native_amount'], // works
            'bills'              => ['native_amount_min', 'native_amount_max'], // works
            'budget_limits'      => ['native_amount'], // works
            'piggy_bank_events'  => ['native_amount'], // works
            'piggy_banks'        => ['native_target_amount'], // works
            'transactions'       => ['native_amount', 'native_foreign_amount'], // works

            // TODO button to recalculate all native amounts on selected pages?

        ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $table => $fields) {
            foreach ($fields as $field) {
                Schema::table($table, static function (Blueprint $table) use ($field): void {
                    // add amount column
                    $table->decimal($field, 32, 12)->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $table => $fields) {
            foreach ($fields as $field) {
                Schema::table($table, static function (Blueprint $table) use ($field): void {
                    // add amount column
                    $table->dropColumn($field);
                });
            }
        }
    }
};
