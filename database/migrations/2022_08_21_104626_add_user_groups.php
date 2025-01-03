<?php

/*
 * 2022_08_21_104626_add_user_groups.php
 * Copyright (c) 2022 james@firefly-iii.org
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

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @SuppressWarnings("PHPMD.ShortMethodName")
     */
    public function up(): void
    {
        try {
            Schema::table(
                'currency_exchange_rates',
                static function (Blueprint $table): void {
                    if (!Schema::hasColumn('currency_exchange_rates', 'user_group_id')) {
                        $table->bigInteger('user_group_id', false, true)->nullable()->after('user_id');
                        $table->foreign('user_group_id', 'cer_to_ugi')->references('id')->on('user_groups')->onDelete('set null')->onUpdate('cascade');
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
                'currency_exchange_rates',
                static function (Blueprint $table): void {
                    if ('sqlite' !== config('database.default')) {
                        $table->dropForeign('cer_to_ugi');
                    }
                    if (Schema::hasColumn('currency_exchange_rates', 'user_group_id')) {
                        $table->dropColumn('user_group_id');
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }
    }
};
