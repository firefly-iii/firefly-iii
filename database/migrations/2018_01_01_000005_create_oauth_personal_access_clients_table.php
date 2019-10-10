<?php

/**
 * 2018_01_01_000005_create_oauth_personal_access_clients_table.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
 * Class CreateOauthPersonalAccessClientsTable
 */
class CreateOauthPersonalAccessClientsTable extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('oauth_personal_access_clients');

    }

    /**
     * Run the migrations.
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up(): void
    {
        Schema::create(
            'oauth_personal_access_clients', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('client_id')->index();
            $table->timestamps();
        }
        );
    }
}
