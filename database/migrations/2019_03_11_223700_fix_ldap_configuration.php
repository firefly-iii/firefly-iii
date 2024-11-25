<?php

/**
 * 2019_03_11_223700_fix_ldap_configuration.php
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
 * Class FixLdapConfiguration.
 *
 * @codeCoverageIgnore
 */
class FixLdapConfiguration extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'objectguid')) {
            try {
                Schema::table(
                    'users',
                    static function (Blueprint $table): void {
                        $table->dropColumn(['objectguid']);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up(): void
    {
        /*
         * ADLdap2 appears to require the ability to store an objectguid for LDAP users
         * now. To support this, we add the column.
         */
        if (!Schema::hasColumn('users', 'objectguid')) {
            try {
                Schema::table(
                    'users',
                    static function (Blueprint $table): void {
                        $table->uuid('objectguid')->nullable()->after('id');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
    }
}
