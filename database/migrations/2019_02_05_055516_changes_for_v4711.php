<?php

/**
 * 2019_02_05_055516_changes_for_v4711.php
 * Copyright (c) 2019 james@firefly-iii.org
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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangesForV4711 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        //
    }

    /**
     * Run the migrations.
     * @SuppressWarnings(PHPMD.ShortMethodName)
     *
     * @return void
     */
    public function up(): void
    {
        /**
         * In 4.7.11, I changed the date field to a "datetimetz" field. This wreaks havoc
         * because apparently MySQL is not actually capable of handling multiple time zones,
         * only having a server wide time zone setting. Actual database schemes like Postgres
         * handle this just fine but the combination is unpredictable. So we go back to
         * datetime (without a time zone) for all database engines because MySQL refuses to play
         * nice.
         */
        Schema::table(
            'transaction_journals', function (Blueprint $table) {
            $table->dateTime('date')->change();
        }
        );

        Schema::table(
            'preferences', function (Blueprint $table) {
            $table->text('data')->nullable()->change();
        }
        );
    }
}
