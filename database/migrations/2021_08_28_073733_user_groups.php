<?php

/*
 * 2021_08_28_073733_user_groups.php
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
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class UserGroups
 */
class UserGroups extends Migration
{
    private array $tables
        = [
            'accounts',
            'attachments',
            'available_budgets',
            'bills',
            'budgets',
            'categories',
            'recurrences',
            'object_groups',
            'preferences',
            'rule_groups',
            'rules',
            'tags',
            'transaction_groups',
            'transaction_journals',
            'webhooks',
        ];

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // remove columns from tables
        /** @var string $tableName */
        foreach ($this->tables as $tableName) {
            if (Schema::hasColumn($tableName, 'user_group_id')) {
                try {
                    Schema::table(
                        $tableName,
                        static function (Blueprint $table) use ($tableName): void {
                            if ('sqlite' !== config('database.default')) {
                                $table->dropForeign(sprintf('%s_to_ugi', $tableName));
                            }
                            if (Schema::hasColumn($tableName, 'user_group_id')) {
                                $table->dropColumn('user_group_id');
                            }
                        }
                    );
                } catch (QueryException $e) {
                    app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                    app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
                }
            }
        }

        if (Schema::hasColumn('users', 'user_group_id')) {
            try {
                Schema::table(
                    'users',
                    static function (Blueprint $table): void {
                        if ('sqlite' !== config('database.default')) {
                            $table->dropForeign('type_user_group_id');
                        }
                        if (Schema::hasColumn('users', 'user_group_id')) {
                            $table->dropColumn('user_group_id');
                        }
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        Schema::dropIfExists('group_memberships');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('user_groups');
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(): void
    {
        /*
         * user is a member of a user_group through a user_group_role
         * may have multiple roles in a group
         */
        if (!Schema::hasTable('user_groups')) {
            try {
                Schema::create(
                    'user_groups',
                    static function (Blueprint $table): void {
                        $table->bigIncrements('id');
                        $table->timestamps();
                        $table->softDeletes();

                        $table->string('title', 255);
                        $table->unique('title');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "user_groups": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }
        if (!Schema::hasTable('user_roles')) {
            try {
                Schema::create(
                    'user_roles',
                    static function (Blueprint $table): void {
                        $table->bigIncrements('id');
                        $table->timestamps();
                        $table->softDeletes();

                        $table->string('title', 255);
                        $table->unique('title');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "user_roles": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }
        if (!Schema::hasTable('group_memberships')) {
            try {
                Schema::create(
                    'group_memberships',
                    static function (Blueprint $table): void {
                        $table->bigIncrements('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->integer('user_id', false, true);
                        $table->bigInteger('user_group_id', false, true);
                        $table->bigInteger('user_role_id', false, true);

                        $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
                        $table->foreign('user_group_id')->references('id')->on('user_groups')->onUpdate('cascade')->onDelete('cascade');
                        $table->foreign('user_role_id')->references('id')->on('user_roles')->onUpdate('cascade')->onDelete('cascade');
                        $table->unique(['user_id', 'user_group_id', 'user_role_id']);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "group_memberships": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }

        try {
            Schema::table(
                'users',
                static function (Blueprint $table): void {
                    if (!Schema::hasColumn('users', 'user_group_id')) {
                        $table->bigInteger('user_group_id', false, true)->nullable();
                        $table->foreign('user_group_id', 'type_user_group_id')->references('id')->on('user_groups')->onDelete('set null')->onUpdate(
                            'cascade'
                        );
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }

        // ADD columns to tables
        /** @var string $tableName */
        foreach ($this->tables as $tableName) {
            try {
                Schema::table(
                    $tableName,
                    static function (Blueprint $table) use ($tableName): void {
                        if (!Schema::hasColumn($tableName, 'user_group_id')) {
                            $table->bigInteger('user_group_id', false, true)->nullable()->after('user_id');
                            $table->foreign('user_group_id', sprintf('%s_to_ugi', $tableName))->references('id')->on('user_groups')->onDelete(
                                'set null'
                            )->onUpdate('cascade');
                        }
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
    }
}
