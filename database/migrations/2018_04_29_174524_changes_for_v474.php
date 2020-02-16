<?php

/**
 * 2018_04_29_174524_changes_for_v474.php
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

/**
 * Class ChangesForV474
 */
class ChangesForV474 extends Migration
{
    /**
     * Reverse the migrations.
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return void
     */
    public function down(): void
    {
        // split up for sqlite compatibility.
        Schema::table(
            'import_jobs',
            static function (Blueprint $table) {

                // cannot drop foreign keys in SQLite:
                if ('sqlite' !== config('database.default')) {
                    $table->dropForeign('import_jobs_tag_id_foreign');
                }
            }
        );

        Schema::table(
            'import_jobs',
            static function (Blueprint $table) {
                $table->dropColumn('provider');

            }
        );

        Schema::table(
            'import_jobs',
            static function (Blueprint $table) {
                $table->dropColumn('stage');

            }
        );

        Schema::table(
            'import_jobs',
            static function (Blueprint $table) {
                $table->dropColumn('transactions');

            }
        );

        Schema::table(
            'import_jobs',
            static function (Blueprint $table) {
                $table->dropColumn('errors');

            }
        );

        Schema::table(
            'import_jobs',
            static function (Blueprint $table) {
                $table->dropColumn('tag_id');

            }
        );
    }

    /**
     * Run the migrations.
     * @SuppressWarnings(PHPMD.ShortMethodName)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table(
            'import_jobs',
            static function (Blueprint $table) {
                $table->string('provider', 50)->after('file_type')->default('');
                $table->string('stage', 50)->after('status')->default('');
                $table->longText('transactions')->after('extended_status')->nullable();
                $table->longText('errors')->after('transactions')->nullable();

                $table->integer('tag_id', false, true)->nullable()->after('user_id');
                $table->foreign('tag_id')->references('id')->on('tags')->onDelete('set null');
            }
        );
    }
}
