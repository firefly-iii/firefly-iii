<?php


/**
 * 2019_03_11_223700_fix_ldap_configuration.php
 * Copyright (c) 2019 https://github.com/wrouesnel
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
 * Class FixLdapConfiguration
 */
class FixLdapConfiguration extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table(
            'users', function (Blueprint $table) {
            $table->dropColumn(['objectguid']);
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
        /**
         * ADLdap2 appears to require the ability to store an objectguid for LDAP users
         * now. To support this, we add the column.
         */
        Schema::table(
            'users', function (Blueprint $table) {
            $table->uuid('objectguid')->nullable()->after('id');
        }
        );
    }
}
