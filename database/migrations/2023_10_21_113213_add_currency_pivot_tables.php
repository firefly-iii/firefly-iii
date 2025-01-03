<?php

/*
 * 2023_10_21_113213_add_currency_pivot_tables.php
 * Copyright (c) 2023 james@firefly-iii.org
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

return new class () extends Migration {
    /**
     * @SuppressWarnings("PHPMD.ShortMethodName")
     * Run the migrations.
     */
    public function up(): void
    {
        // transaction_currency_user
        if (!Schema::hasTable('transaction_currency_user')) {
            try {
                Schema::create('transaction_currency_user', static function (Blueprint $table): void {
                    $table->id();
                    $table->timestamps();
                    $table->integer('user_id', false, true);
                    $table->integer('transaction_currency_id', false, true);
                    $table->boolean('user_default')->default(false);
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
                    $table->unique(['user_id', 'transaction_currency_id'], 'unique_combo');
                });
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "transaction_currency_user": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }

        // transaction_currency_user_group
        if (!Schema::hasTable('transaction_currency_user_group')) {
            try {
                Schema::create('transaction_currency_user_group', static function (Blueprint $table): void {
                    $table->id();
                    $table->timestamps();
                    $table->bigInteger('user_group_id', false, true);
                    $table->integer('transaction_currency_id', false, true);
                    $table->boolean('group_default')->default(false);
                    $table->foreign('user_group_id')->references('id')->on('user_groups')->onDelete('cascade');
                    $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
                    $table->unique(['user_group_id', 'transaction_currency_id'], 'unique_combo_ug');
                });
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "transaction_currency_user_group": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_currency_user');
        Schema::dropIfExists('transaction_currency_user_group');
    }
};
