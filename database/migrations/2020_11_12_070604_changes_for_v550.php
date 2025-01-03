<?php

/*
 * 2020_11_12_070604_changes_for_v550.php
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
 * Class ChangesForV550
 */
class ChangesForV550 extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // recreate jobs table.
        Schema::dropIfExists('jobs');

        if (!Schema::hasTable('jobs')) {
            try {
                Schema::create(
                    'jobs',
                    static function (Blueprint $table): void {
                        // straight from Laravel (this is the OLD table)
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
                app('log')->error(sprintf('Could not create table "jobs": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }

        // expand budget / transaction journal table.
        if (Schema::hasColumn('budget_transaction_journal', 'budget_limit_id')) {
            try {
                Schema::table(
                    'budget_transaction_journal',
                    static function (Blueprint $table): void {
                        if ('sqlite' !== config('database.default')) {
                            $table->dropForeign('budget_id_foreign');
                        }
                        $table->dropColumn('budget_limit_id');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        // drop failed jobs table.
        Schema::dropIfExists('failed_jobs');

        // drop fields from budget limits
        // in two steps for sqlite
        if (Schema::hasColumn('budget_limits', 'period')) {
            try {
                Schema::table(
                    'budget_limits',
                    static function (Blueprint $table): void {
                        $table->dropColumn('period');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }
        if (Schema::hasColumn('budget_limits', 'generated')) {
            try {
                Schema::table(
                    'budget_limits',
                    static function (Blueprint $table): void {
                        $table->dropColumn('generated');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        // drop other tables
        Schema::dropIfExists('webhook_attempts');
        Schema::dropIfExists('webhook_messages');
        Schema::dropIfExists('webhooks');
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings("PHPMD.ShortMethodName")
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    public function up(): void
    {
        // drop and recreate jobs table.
        Schema::dropIfExists('jobs');
        // this is the NEW table
        if (!Schema::hasTable('jobs')) {
            try {
                Schema::create(
                    'jobs',
                    static function (Blueprint $table): void {
                        $table->bigIncrements('id');
                        $table->string('queue')->index();
                        $table->longText('payload');
                        $table->unsignedTinyInteger('attempts');
                        $table->unsignedInteger('reserved_at')->nullable();
                        $table->unsignedInteger('available_at');
                        $table->unsignedInteger('created_at');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "jobs": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }
        // drop failed jobs table.
        Schema::dropIfExists('failed_jobs');

        // create new failed_jobs table.
        if (!Schema::hasTable('failed_jobs')) {
            try {
                Schema::create(
                    'failed_jobs',
                    static function (Blueprint $table): void {
                        $table->bigIncrements('id');
                        $table->string('uuid')->unique();
                        $table->text('connection');
                        $table->text('queue');
                        $table->longText('payload');
                        $table->longText('exception');
                        $table->timestamp('failed_at')->useCurrent();
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "failed_jobs": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }

        // update budget / transaction journal table.
        if (!Schema::hasColumn('budget_transaction_journal', 'budget_limit_id')) {
            try {
                Schema::table(
                    'budget_transaction_journal',
                    static function (Blueprint $table): void {
                        if (!Schema::hasColumn('budget_transaction_journal', 'budget_limit_id')) {
                            $table->integer('budget_limit_id', false, true)->nullable()->default(null)->after('budget_id');
                            $table->foreign('budget_limit_id', 'budget_id_foreign')->references('id')->on('budget_limits')->onDelete('set null');
                        }
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
                app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
            }
        }

        // append budget limits table.
        // I swear I dropped & recreated this field 15 times already.

        try {
            Schema::table(
                'budget_limits',
                static function (Blueprint $table): void {
                    if (!Schema::hasColumn('budget_limits', 'period')) {
                        $table->string('period', 12)->nullable();
                    }
                    if (!Schema::hasColumn('budget_limits', 'generated')) {
                        $table->boolean('generated')->default(false);
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }

        // new webhooks table
        if (!Schema::hasTable('webhooks')) {
            try {
                Schema::create(
                    'webhooks',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->integer('user_id', false, true);
                        $table->string('title', 255)->index();
                        $table->string('secret', 32)->index();
                        $table->boolean('active')->default(true);
                        $table->unsignedSmallInteger('trigger');
                        $table->unsignedSmallInteger('response');
                        $table->unsignedSmallInteger('delivery');
                        $table->string('url', 1024);
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "webhooks": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }

        // new webhook_messages table
        if (!Schema::hasTable('webhook_messages')) {
            try {
                Schema::create(
                    'webhook_messages',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->boolean('sent')->default(false);
                        $table->boolean('errored')->default(false);

                        $table->integer('webhook_id', false, true);
                        $table->string('uuid', 64);
                        $table->longText('message');

                        $table->foreign('webhook_id')->references('id')->on('webhooks')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "webhook_messages": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }

        if (!Schema::hasTable('webhook_attempts')) {
            try {
                Schema::create(
                    'webhook_attempts',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->integer('webhook_message_id', false, true);
                        $table->unsignedSmallInteger('status_code')->default(0);

                        $table->longText('logs')->nullable();
                        $table->longText('response')->nullable();

                        $table->foreign('webhook_message_id')->references('id')->on('webhook_messages')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "webhook_attempts": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }
    }
}
