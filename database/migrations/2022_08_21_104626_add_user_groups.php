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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 *
 */
return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table(
            'currency_exchange_rates',
            function (Blueprint $table) {
                if (!Schema::hasColumn('currency_exchange_rates', 'user_group_id')) {
                    try {
                        $table->bigInteger('user_group_id', false, true)->nullable()->after('user_id');
                    } catch (QueryException $e) {
                        Log::error(sprintf('Could not add column "user_group_id" to table "currency_exchange_rates": %s', $e->getMessage()));
                        Log::error('If the column exists already (see error), this is not a problem. Otherwise, please create a GitHub discussion.');
                    }
                    try {
                        $table->foreign('user_group_id', 'cer_to_ugi')->references('id')->on('user_groups')->onDelete('set null')->onUpdate('cascade');
                    } catch (QueryException $e) {
                        Log::error(sprintf('Could not add foreign key "cer_to_ugi" to table "currency_exchange_rates": %s', $e->getMessage()));
                        Log::error('If the foreign key exists already (see error), this is not a problem. Otherwise, please create a GitHub discussion.');
                    }
                }
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table(
            'currency_exchange_rates',
            function (Blueprint $table) {
                try {
                    $table->dropForeign('cer_to_ugi');
                } catch (QueryException $e) {
                    Log::error(sprintf('Could not drop foreign key "cer_to_ugi" from table "currency_exchange_rates": %s', $e->getMessage()));
                    Log::error('If the foreign key does not exist (see error message), this is not a problem. Otherwise, please create a GitHub discussion.');
                }
                if (Schema::hasColumn('currency_exchange_rates', 'user_group_id')) {
                    try {
                        $table->dropColumn('user_group_id');
                    } catch (QueryException $e) {
                        Log::error(sprintf('Could not drop column "user_group_id" from table "currency_exchange_rates": %s', $e->getMessage()));
                        Log::error('If the column does not exist (see error message), this is not a problem. Otherwise, please create a GitHub discussion.');
                    }
                }
            }
        );
    }
};
