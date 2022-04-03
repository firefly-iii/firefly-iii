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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class UserGroups
 */
class UserGroups extends Migration
{
    private array $tables
        = ['accounts', 'attachments', 'available_budgets', 'bills', 'budgets', 'categories', 'recurrences', 'rule_groups', 'rules', 'tags',
           'transaction_groups', 'transaction_journals', 'webhooks'];

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // remove columns from tables
        /** @var string $tableName */
        foreach ($this->tables as $tableName) {
            Schema::table(
                $tableName, function (Blueprint $table) use ($tableName) {

                $table->dropForeign(sprintf('%s_to_ugi', $tableName));
                if (Schema::hasColumn($tableName, 'user_group_id')) {
                    $table->dropColumn('user_group_id');
                }
            }
            );
        }

        Schema::table(
            'users', function (Blueprint $table) {

            $table->dropForeign('type_user_group_id');
            if (Schema::hasColumn('users', 'user_group_id')) {
                $table->dropColumn('user_group_id');
            }

        }
        );

        Schema::dropIfExists('group_memberships');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('user_groups');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*
         * user is a member of a user_group through a user_group_role
         * may have multiple roles in a group
         */
        Schema::create(
            'user_groups', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->softDeletes();

            $table->string('title', 255);
            $table->unique('title');
        }
        );

        Schema::create(
            'user_roles', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->softDeletes();

            $table->string('title', 255);
            $table->unique('title');
        }
        );

        Schema::create(
            'group_memberships',
            static function (Blueprint $table) {
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
        Schema::table(
            'users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'user_group_id')) {
                $table->bigInteger('user_group_id', false, true)->nullable();
                $table->foreign('user_group_id', 'type_user_group_id')->references('id')->on('user_groups')->onDelete('set null')->onUpdate('cascade');
            }
        }
        );

        // ADD columns from tables
        /** @var string $tableName */
        foreach ($this->tables as $tableName) {
            Schema::table(
                $tableName, function (Blueprint $table) use ($tableName) {

                if (!Schema::hasColumn($tableName, 'user_group_id')) {
                    $table->bigInteger('user_group_id', false, true)->nullable()->after('user_id');
                    $table->foreign('user_group_id', sprintf('%s_to_ugi', $tableName))->references('id')->on('user_groups')->onDelete('set null')->onUpdate('cascade');
                }
            }
            );
        }

    }
}
