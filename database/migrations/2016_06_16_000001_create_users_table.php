<?php
/**
 * 2016_06_16_000001_create_users_table.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateUsersTable
 */
class CreateUsersTable extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::drop('users');
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up()
    {
        if (!Schema::hasTable('users')) {
            Schema::create(
                'users',
                function (Blueprint $table) {
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
        }
    }
}
