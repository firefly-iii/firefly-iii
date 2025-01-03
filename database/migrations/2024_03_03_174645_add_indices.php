<?php


/*
 * 2024_03_03_174645_add_indices.php
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
    private const string QUERY_ERROR = 'Could not execute query (table "%s", field "%s"): %s';
    private const string EXPL        = 'If the index already exists (see error), or if MySQL can\'t do it, this is not an problem. Otherwise, please open a GitHub discussion.';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // add missing indices
        $set = [
            'account_meta'                 => ['account_id'],
            'accounts'                     => ['user_id', 'user_group_id', 'account_type_id'],
            'budgets'                      => ['user_id', 'user_group_id'],
            'journal_meta'                 => ['transaction_journal_id', 'data', 'name'],
            'category_transaction_journal' => ['transaction_journal_id'],
            'categories'                   => ['user_id', 'user_group_id'],
            'transaction_groups'           => ['user_id', 'user_group_id'],
            'transaction_journals'         => ['user_id', 'user_group_id', 'date', 'transaction_group_id', 'transaction_type_id', 'transaction_currency_id', 'bill_id'],
            'transactions'                 => ['account_id', 'transaction_journal_id', 'transaction_currency_id', 'foreign_currency_id'],
        ];

        foreach ($set as $table => $fields) {
            foreach ($fields as $field) {
                try {
                    Schema::table(
                        $table,
                        static function (Blueprint $blueprint) use ($field): void {
                            $blueprint->index($field);
                        }
                    );
                } catch (QueryException $e) {
                    app('log')->error(sprintf(self::QUERY_ERROR, $table, $field, $e->getMessage()));
                    app('log')->error(self::EXPL);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
