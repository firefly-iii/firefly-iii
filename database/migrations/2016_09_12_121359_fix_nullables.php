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
use Illuminate\Support\Facades\Schema;

/**
 * Class FixNullables.
 *
 * @codeCoverageIgnore
 */
class FixNullables extends Migration
{
    private const COLUMN_ALREADY_EXISTS = 'If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.';
    private const TABLE_UPDATE_ERROR    = 'Could not update table "%s": %s';

    /**
     * Reverse the migrations.
     */
    public function down(): void {}

    /**
     * Run the migrations.
     *
     * @SuppressWarnings("PHPMD.ShortMethodName")
     */
    public function up(): void
    {
        if (!Schema::hasColumn('rule_groups', 'description')) {
            try {
                Schema::table(
                    'rule_groups',
                    static function (Blueprint $table): void {
                        $table->text('description')->nullable()->change();
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_UPDATE_ERROR, 'rule_groups', $e->getMessage()));
                app('log')->error(self::COLUMN_ALREADY_EXISTS);
            }
        }

        if (!Schema::hasColumn('rules', 'description')) {
            try {
                Schema::table(
                    'rules',
                    static function (Blueprint $table): void {
                        $table->text('description')->nullable()->change();
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_UPDATE_ERROR, 'rules', $e->getMessage()));
                app('log')->error(self::COLUMN_ALREADY_EXISTS);
            }
        }
    }
}
