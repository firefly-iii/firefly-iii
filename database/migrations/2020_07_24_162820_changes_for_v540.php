<?php

/*
 * 2020_07_24_162820_changes_for_v540.php
 * Copyright (c) 2021 james@firefly-iii.org
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

/**
 * Class ChangesForV540
 *
 * @codeCoverageIgnore
 */
class ChangesForV540 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table(
            'oauth_clients', static function (Blueprint $table) {
            $table->dropColumn('provider');
        }
        );

        Schema::table(
            'accounts', static function (Blueprint $table) {
            $table->dropColumn('order');
        }
        );

        Schema::table(
            'bills', static function (Blueprint $table) {
            $table->dropColumn('end_date');
            $table->dropColumn('extension_date');
        }
        );
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table(
            'accounts', static function (Blueprint $table) {
            $table->integer('order', false, true)->default(0);
        }
        );
        Schema::table(
            'oauth_clients', static function (Blueprint $table) {
            $table->string('provider')->nullable();
        }
        );
        Schema::table(
            'bills', static function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('date');
            $table->date('extension_date')->nullable()->after('end_date');
        }
        );
        // make column nullable:
        Schema::table(
            'oauth_clients', function (Blueprint $table) {
            $table->string('secret', 100)->nullable()->change();
        }
        );
    }
}
