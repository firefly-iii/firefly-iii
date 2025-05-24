<?php

/**
 * 2016_06_16_000000_create_support_tables.php
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
 * Class CreateSupportTables.
 *
 * @codeCoverageIgnore
 */
class CreateSupportTables extends Migration
{
    private const TABLE_ALREADY_EXISTS = 'If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.';
    private const TABLE_ERROR          = 'Could not create table "%s": %s';

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_types');
        Schema::dropIfExists('transaction_currencies');
        Schema::dropIfExists('transaction_types');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('configuration');
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings("PHPMD.ShortMethodName")
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

    private function createAccountTypeTable(): void
    {
        if (!Schema::hasTable('account_types')) {
            try {
                Schema::create(
                    'account_types',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->string('type', 50);

                        // type must be unique.
                        $table->unique(['type']);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'account_types', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createCurrencyTable(): void
    {
        if (!Schema::hasTable('transaction_currencies')) {
            try {
                Schema::create(
                    'transaction_currencies',
                    static function (Blueprint $table): void {
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
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'transaction_currencies', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createTransactionTypeTable(): void
    {
        if (!Schema::hasTable('transaction_types')) {
            try {
                Schema::create(
                    'transaction_types',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->string('type', 50);

                        // type must be unique.
                        $table->unique(['type']);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'transaction_types', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createJobsTable(): void
    {
        if (!Schema::hasTable('jobs')) {
            try {
                Schema::create(
                    'jobs',
                    static function (Blueprint $table): void {
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
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'jobs', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createPasswordTable(): void
    {
        if (!Schema::hasTable('password_resets')) {
            try {
                Schema::create(
                    'password_resets',
                    static function (Blueprint $table): void {
                        // straight from laravel
                        $table->string('email')->index();
                        $table->string('token')->index();
                        $table->timestamp('created_at')->nullable();
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'password_resets', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createPermissionsTable(): void
    {
        if (!Schema::hasTable('permissions')) {
            try {
                Schema::create(
                    'permissions',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->string('name')->unique();
                        $table->string('display_name')->nullable();
                        $table->string('description')->nullable();
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'permissions', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createRolesTable(): void
    {
        if (!Schema::hasTable('roles')) {
            try {
                Schema::create(
                    'roles',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->string('name')->unique();
                        $table->string('display_name')->nullable();
                        $table->string('description')->nullable();
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'roles', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createPermissionRoleTable(): void
    {
        if (!Schema::hasTable('permission_role')) {
            try {
                Schema::create(
                    'permission_role',
                    static function (Blueprint $table): void {
                        $table->integer('permission_id')->unsigned();
                        $table->integer('role_id')->unsigned();

                        $table->foreign('permission_id')->references('id')->on('permissions')->onUpdate('cascade')->onDelete('cascade');
                        $table->foreign('role_id')->references('id')->on('roles')->onUpdate('cascade')->onDelete('cascade');

                        $table->primary(['permission_id', 'role_id']);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'permission_role', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createSessionsTable(): void
    {
        if (!Schema::hasTable('sessions')) {
            try {
                Schema::create(
                    'sessions',
                    static function (Blueprint $table): void {
                        $table->string('id')->unique();
                        $table->integer('user_id')->nullable();
                        $table->string('ip_address', 45)->nullable();
                        $table->text('user_agent')->nullable();
                        $table->text('payload');
                        $table->integer('last_activity');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'sessions', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createConfigurationTable(): void
    {
        if (!Schema::hasTable('configuration')) {
            try {
                Schema::create(
                    'configuration',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->string('name', 50);
                        $table->text('data');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'configuration', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }
}
