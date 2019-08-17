<?php


/**
 * 2019_02_11_170529_changes_for_v4712.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class ChangesForV4712
 */
class ChangesForV4712 extends Migration
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
    }
}
