<?php

/**
 * 2016_06_16_000001_create_users_table.php
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
 * Class CreateUsersTable.
 *
 * @codeCoverageIgnore
 */
class CreateUsersTable extends Migration
{
    private const TABLE_ALREADY_EXISTS = 'If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.';
    private const TABLE_ERROR          = 'Could not create table "%s": %s';

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings("PHPMD.ShortMethodName")
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            try {
                Schema::create(
                    'users',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->string('email', 255);
                        $table->string('password', 60);
                        $table->string('remember_token', 100)->nullable();
                        $table->string('reset', 32)->nullable();
                        $table->tinyInteger('blocked', false, true)->default('0');
                        $table->string('blocked_code', 25)->nullable();
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'users', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }
}
