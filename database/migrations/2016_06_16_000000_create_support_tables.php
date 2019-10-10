<?php
/**
 * 2016_06_16_000000_create_support_tables.php
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

/**
 * Class CreateSupportTables
 */
class CreateSupportTables extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('account_types');
        Schema::drop('transaction_currencies');
        Schema::drop('transaction_types');
        Schema::drop('jobs');
        Schema::drop('password_resets');
        Schema::drop('permission_role');
        Schema::drop('permissions');
        Schema::drop('roles');
        Schema::drop('sessions');
        Schema::drop('configuration');
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up(): void
    {
        $this->createAccountTypeTable();
        $this->createCurrencyTable();
        $this->createTransactionTypeTable();
        $this->createJobsTable();
        $this->createPasswordTable();
        $this->createPermissionsTable();
        $this->createRolesTable();
        $this->createPermissionRoleTable();
        $this->createSessionsTable();
        $this->createConfigurationTable();
    }

    /**
     *
     */
    private function createAccountTypeTable(): void
    {
        if (!Schema::hasTable('account_types')) {
            Schema::create(
                'account_types',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->string('type', 50);

                    // type must be unique.
                    $table->unique(['type']);
                }
            );
        }
    }

    private function createConfigurationTable(): void
    {
        if (!Schema::hasTable('configuration')) {
            Schema::create(
                'configuration',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->softDeletes();
                    $table->string('name', 50);
                    $table->text('data');
                    $table->unique(['name']);
                }
            );
        }
    }

    /**
     *
     */
    private function createCurrencyTable(): void
    {
        if (!Schema::hasTable('transaction_currencies')) {
            Schema::create(
                'transaction_currencies',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->softDeletes();
                    $table->string('code', 3);
                    $table->string('name', 255);
                    $table->string('symbol', 12);

                    // code must be unique.
                    $table->unique(['code']);
                }
            );
        }
    }

    /**
     *
     */
    private function createJobsTable(): void
    {
        if (!Schema::hasTable('jobs')) {
            Schema::create(
                'jobs',
                function (Blueprint $table) {
                    // straight from Laravel
                    $table->bigIncrements('id');
                    $table->string('queue');
                    $table->longText('payload');
                    $table->tinyInteger('attempts')->unsigned();
                    $table->tinyInteger('reserved')->unsigned();
                    $table->unsignedInteger('reserved_at')->nullable();
                    $table->unsignedInteger('available_at');
                    $table->unsignedInteger('created_at');
                    $table->index(['queue', 'reserved', 'reserved_at']);
                }
            );
        }
    }

    /**
     *
     */
    private function createPasswordTable(): void
    {
        if (!Schema::hasTable('password_resets')) {
            Schema::create(
                'password_resets',
                function (Blueprint $table) {
                    // straight from laravel
                    $table->string('email')->index();
                    $table->string('token')->index();
                    $table->timestamp('created_at')->nullable();
                }
            );
        }
    }

    /**
     *
     */
    private function createPermissionRoleTable(): void
    {
        if (!Schema::hasTable('permission_role')) {
            Schema::create(
                'permission_role',
                function (Blueprint $table) {
                    $table->integer('permission_id')->unsigned();
                    $table->integer('role_id')->unsigned();

                    $table->foreign('permission_id')->references('id')->on('permissions')->onUpdate('cascade')->onDelete('cascade');
                    $table->foreign('role_id')->references('id')->on('roles')->onUpdate('cascade')->onDelete('cascade');

                    $table->primary(['permission_id', 'role_id']);
                }
            );
        }
    }

    /**
     *
     */
    private function createPermissionsTable(): void
    {
        if (!Schema::hasTable('permissions')) {
            Schema::create(
                'permissions',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->string('name')->unique();
                    $table->string('display_name')->nullable();
                    $table->string('description')->nullable();
                }
            );
        }
    }

    /**
     *
     */
    private function createRolesTable(): void
    {
        if (!Schema::hasTable('roles')) {
            Schema::create(
                'roles',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->string('name')->unique();
                    $table->string('display_name')->nullable();
                    $table->string('description')->nullable();
                }
            );
        }
    }

    /**
     *
     */
    private function createSessionsTable(): void
    {
        if (!Schema::hasTable('sessions')) {
            Schema::create(
                'sessions',
                function (Blueprint $table) {
                    $table->string('id')->unique();
                    $table->integer('user_id')->nullable();
                    $table->string('ip_address', 45)->nullable();
                    $table->text('user_agent')->nullable();
                    $table->text('payload');
                    $table->integer('last_activity');
                }
            );
        }
    }

    /**
     *
     */
    private function createTransactionTypeTable(): void
    {
        if (!Schema::hasTable('transaction_types')) {
            Schema::create(
                'transaction_types',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->softDeletes();
                    $table->string('type', 50);

                    // type must be unique.
                    $table->unique(['type']);
                }
            );
        }
    }
}
