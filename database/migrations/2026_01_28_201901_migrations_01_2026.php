<?php


/*
 * 2026_01_28_201901_migrations_01_2026.php
 * Copyright (c) 2026 james@firefly-iii.org
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes #11620
 */
return new class extends Migration {
    /**
     * Reverse the migrations.
     */
    public function down(): void {}

    public function up(): void
    {
        try {
            Schema::table('transactions', static function (Blueprint $blueprint): void {
                $blueprint->index(['transaction_journal_id', 'amount'], 'idx_tx_journal_amount');
            });
        } catch (QueryException $e) {
            $message = $e->getMessage();

            // ignore duplicate key name as error.
            if (str_contains($message, ' Duplicate key name')) {
                return;
            }
            Log::error(sprintf('Error when creating index: %s', $e->getMessage()));
        }
        try {
            Schema::table('tag_transaction_journal', static function (Blueprint $blueprint): void {
                $blueprint->index(['transaction_journal_id', 'tag_id'], 'idx_ttj_journal_tag');
            });
        } catch (QueryException $e) {
            $message = $e->getMessage();

            // ignore duplicate key name as error.
            if (str_contains($message, ' Duplicate key name')) {
                return;
            }
            Log::error(sprintf('Error when creating index: %s', $e->getMessage()));
        }
        try {
            Schema::table('transaction_journals', static function (Blueprint $blueprint): void {
                $blueprint->index(['deleted_at'], 'idx_tj_deleted');
            });
        } catch (QueryException $e) {
            $message = $e->getMessage();

            // ignore duplicate key name as error.
            if (str_contains($message, ' Duplicate key name')) {
                return;
            }
            Log::error(sprintf('Error when creating index: %s', $e->getMessage()));
        }
    }
};
