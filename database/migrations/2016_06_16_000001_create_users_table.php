<?php
/**
 * 2016_06_16_000001_create_users_table.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
