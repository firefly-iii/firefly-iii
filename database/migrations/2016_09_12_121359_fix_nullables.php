<?php

/**
 * 2016_09_12_121359_fix_nullables.php
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
 * Class FixNullables.
 *
 * @codeCoverageIgnore
 */
class FixNullables extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }

    /**
     * Run the migrations.
     *
     */
    public function up(): void
    {
        if (!Schema::hasColumn('rule_groups', 'description')) {
            try {
                Schema::table(
                    'rule_groups',
                    static function (Blueprint $table) {
                        $table->text('description')->nullable()->change();
                    }
                );
            } catch (QueryException $e) {
                Log::error(sprintf('Could not update table: %s', $e->getMessage()));
                Log::error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        if (!Schema::hasColumn('rules', 'description')) {
            try {
                Schema::table(
                    'rules',
                    static function (Blueprint $table) {
                        $table->text('description')->nullable()->change();
                    }
                );
            } catch (QueryException $e) {
                Log::error(sprintf('Could not execute query: %s', $e->getMessage()));
                Log::error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
    }
}
