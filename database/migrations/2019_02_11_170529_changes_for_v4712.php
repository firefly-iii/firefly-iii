<?php

/**
 * 2019_02_11_170529_changes_for_v4712.php
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
 * Class ChangesForV4712.
 *
 * @codeCoverageIgnore
 */
class ChangesForV4712 extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void {}

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up(): void
    {
        /*
         * In 4.7.11, I changed the date field to a "datetimetz" field. This wreaks havoc
         * because apparently MySQL is not actually capable of handling multiple time zones,
         * only having a server wide time zone setting. Actual database schemes like Postgres
         * handle this just fine but the combination is unpredictable. So we go back to
         * datetime (without a time zone) for all database engines because MySQL refuses to play
         * nice.
         */
        try {
            Schema::table(
                'transaction_journals',
                static function (Blueprint $table): void {
                    $table->dateTime('date')->change();
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }
    }
}
