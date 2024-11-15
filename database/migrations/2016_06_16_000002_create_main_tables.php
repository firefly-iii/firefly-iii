<?php

/**
 * 2016_06_16_000002_create_main_tables.php
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

/**
 * Class CreateMainTables.
 *
 * @codeCoverageIgnore
 */
class CreateMainTables extends Migration
{
    private const TABLE_ALREADY_EXISTS = 'If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.';
    private const TABLE_ERROR          = 'Could not create table "%s": %s';

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_meta');
        Schema::dropIfExists('piggy_bank_repetitions');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('limit_repetitions');
        Schema::dropIfExists('budget_limits');
        Schema::dropIfExists('export_jobs'); // table is no longer created
        Schema::dropIfExists('import_jobs'); // table is no longer created
        Schema::dropIfExists('preferences');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('rule_actions');
        Schema::dropIfExists('rule_triggers');
        Schema::dropIfExists('rules');
        Schema::dropIfExists('rule_groups');
        Schema::dropIfExists('category_transaction');
        Schema::dropIfExists('budget_transaction');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('piggy_bank_events');
        Schema::dropIfExists('piggy_banks');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('category_transaction_journal');
        Schema::dropIfExists('budget_transaction_journal');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('tag_transaction_journal');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('journal_meta');
        Schema::dropIfExists('transaction_journals');
        Schema::dropIfExists('bills');
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up(): void
    {
        $this->createAccountTables();
        $this->createPiggyBanksTable();
        $this->createAttachmentsTable();
        $this->createBillsTable();
        $this->createBudgetTables();
        $this->createCategoriesTable();
        $this->createPreferencesTable();
        $this->createRoleTable();
        $this->createRuleTables();
        $this->createTagsTable();
        $this->createTransactionTables();
    }

    private function createAccountTables(): void
    {
        if (!Schema::hasTable('accounts')) {
            try {
                Schema::create(
                    'accounts',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->integer('user_id', false, true);
                        $table->integer('account_type_id', false, true);
                        $table->string('name', 1024);
                        $table->decimal('virtual_balance', 32, 12)->nullable();
                        $table->string('iban', 255)->nullable();
                        $table->boolean('active')->default(1);
                        $table->boolean('encrypted')->default(0);
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                        $table->foreign('account_type_id')->references('id')->on('account_types')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'accounts', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }

        if (!Schema::hasTable('account_meta')) {
            try {
                Schema::create(
                    'account_meta',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->integer('account_id', false, true);
                        $table->string('name');
                        $table->text('data');
                        $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'account_meta', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createPiggyBanksTable(): void
    {
        if (!Schema::hasTable('piggy_banks')) {
            try {
                Schema::create(
                    'piggy_banks',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->integer('account_id', false, true);
                        $table->string('name', 1024);
                        $table->decimal('targetamount', 32, 12);
                        $table->date('startdate')->nullable();
                        $table->date('targetdate')->nullable();
                        $table->integer('order', false, true)->default(0);
                        $table->boolean('active')->default(0);
                        $table->boolean('encrypted')->default(1);
                        $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'piggy_banks', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }

        if (!Schema::hasTable('piggy_bank_repetitions')) {
            try {
                Schema::create(
                    'piggy_bank_repetitions',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->integer('piggy_bank_id', false, true);
                        $table->date('startdate')->nullable();
                        $table->date('targetdate')->nullable();
                        $table->decimal('currentamount', 32, 12);
                        $table->foreign('piggy_bank_id')->references('id')->on('piggy_banks')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'piggy_bank_repetitions', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createAttachmentsTable(): void
    {
        if (!Schema::hasTable('attachments')) {
            try {
                Schema::create(
                    'attachments',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->integer('user_id', false, true);
                        $table->integer('attachable_id', false, true);
                        $table->string('attachable_type', 255);
                        $table->string('md5', 128);
                        $table->string('filename', 1024);
                        $table->string('title', 1024)->nullable();
                        $table->text('description')->nullable();
                        $table->text('notes')->nullable();
                        $table->string('mime', 1024);
                        $table->integer('size', false, true);
                        $table->boolean('uploaded')->default(1);

                        // link user id to users table
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'attachments', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createBillsTable(): void
    {
        if (!Schema::hasTable('bills')) {
            try {
                Schema::create(
                    'bills',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->integer('user_id', false, true);
                        $table->string('name', 1024);
                        $table->string('match', 1024);
                        $table->decimal('amount_min', 32, 12);
                        $table->decimal('amount_max', 32, 12);
                        $table->date('date');
                        $table->string('repeat_freq', 30);
                        $table->smallInteger('skip', false, true)->default(0);
                        $table->boolean('automatch')->default(1);
                        $table->boolean('active')->default(1);
                        $table->boolean('name_encrypted')->default(0);
                        $table->boolean('match_encrypted')->default(0);

                        // link user id to users table
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'bills', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createBudgetTables(): void
    {
        if (!Schema::hasTable('budgets')) {
            try {
                Schema::create(
                    'budgets',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->integer('user_id', false, true);
                        $table->string('name', 1024);
                        $table->boolean('active')->default(1);
                        $table->boolean('encrypted')->default(0);
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'budgets', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
        if (!Schema::hasTable('budget_limits')) {
            try {
                Schema::create(
                    'budget_limits',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->integer('budget_id', false, true);
                        $table->date('startdate');
                        $table->decimal('amount', 32, 12);
                        $table->string('repeat_freq', 30)->nullable();
                        $table->boolean('repeats')->default(0);
                        $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'budget_limits', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createCategoriesTable(): void
    {
        if (!Schema::hasTable('categories')) {
            try {
                Schema::create(
                    'categories',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->integer('user_id', false, true);
                        $table->string('name', 1024);
                        $table->boolean('encrypted')->default(0);

                        // link user id to users table
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'categories', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createPreferencesTable(): void
    {
        if (!Schema::hasTable('preferences')) {
            try {
                Schema::create(
                    'preferences',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->integer('user_id', false, true);
                        $table->string('name', 1024);
                        $table->text('data');

                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'preferences', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createRoleTable(): void
    {
        if (!Schema::hasTable('role_user')) {
            try {
                Schema::create(
                    'role_user',
                    static function (Blueprint $table): void {
                        $table->integer('user_id', false, true);
                        $table->integer('role_id', false, true);

                        $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
                        $table->foreign('role_id')->references('id')->on('roles')->onUpdate('cascade')->onDelete('cascade');

                        $table->primary(['user_id', 'role_id']);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'role_user', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function createRuleTables(): void
    {
        if (!Schema::hasTable('rule_groups')) {
            try {
                Schema::create(
                    'rule_groups',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->integer('user_id', false, true);
                        $table->string('title', 255);
                        $table->text('description')->nullable();
                        $table->integer('order', false, true)->default(0);
                        $table->boolean('active')->default(1);

                        // link user id to users table
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'rule_groups', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
        if (!Schema::hasTable('rules')) {
            try {
                Schema::create(
                    'rules',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->integer('user_id', false, true);
                        $table->integer('rule_group_id', false, true);
                        $table->string('title', 255);
                        $table->text('description')->nullable();
                        $table->integer('order', false, true)->default(0);
                        $table->boolean('active')->default(1);
                        $table->boolean('stop_processing')->default(0);

                        // link user id to users table
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

                        // link rule group id to rule group table
                        $table->foreign('rule_group_id')->references('id')->on('rule_groups')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'rules', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
        if (!Schema::hasTable('rule_actions')) {
            try {
                Schema::create(
                    'rule_actions',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->integer('rule_id', false, true);

                        $table->string('action_type', 50);
                        $table->string('action_value', 255);

                        $table->integer('order', false, true)->default(0);
                        $table->boolean('active')->default(1);
                        $table->boolean('stop_processing')->default(0);

                        // link rule id to rules table
                        $table->foreign('rule_id')->references('id')->on('rules')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'rule_actions', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
        if (!Schema::hasTable('rule_triggers')) {
            try {
                Schema::create(
                    'rule_triggers',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->integer('rule_id', false, true);

                        $table->string('trigger_type', 50);
                        $table->string('trigger_value', 255);

                        $table->integer('order', false, true)->default(0);
                        $table->boolean('active')->default(1);
                        $table->boolean('stop_processing')->default(0);

                        // link rule id to rules table
                        $table->foreign('rule_id')->references('id')->on('rules')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'rule_triggers', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    private function createTagsTable(): void
    {
        if (!Schema::hasTable('tags')) {
            try {
                Schema::create(
                    'tags',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->integer('user_id', false, true);

                        $table->string('tag', 1024);
                        $table->string('tagMode', 1024);
                        $table->date('date')->nullable();
                        $table->text('description')->nullable();
                        $table->decimal('latitude', 12, 8)->nullable();
                        $table->decimal('longitude', 12, 8)->nullable();
                        $table->smallInteger('zoomLevel', false, true)->nullable();

                        // link user id to users table
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'tags', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function createTransactionTables(): void
    {
        if (!Schema::hasTable('transaction_journals')) {
            try {
                Schema::create(
                    'transaction_journals',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->integer('user_id', false, true);
                        $table->integer('transaction_type_id', false, true);
                        $table->integer('bill_id', false, true)->nullable();
                        $table->integer('transaction_currency_id', false, true);
                        $table->string('description', 1024);
                        $table->date('date');
                        $table->date('interest_date')->nullable();
                        $table->date('book_date')->nullable();
                        $table->date('process_date')->nullable();
                        $table->integer('order', false, true)->default(0);
                        $table->integer('tag_count', false, true);
                        $table->boolean('encrypted')->default(1);
                        $table->boolean('completed')->default(1);
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                        $table->foreign('transaction_type_id')->references('id')->on('transaction_types')->onDelete('cascade');
                        $table->foreign('bill_id')->references('id')->on('bills')->onDelete('set null');
                        $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'transaction_journals', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }

        if (!Schema::hasTable('journal_meta')) {
            try {
                Schema::create(
                    'journal_meta',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->integer('transaction_journal_id', false, true);
                        $table->string('name', 255);
                        $table->text('data');
                        $table->string('hash', 64);
                        $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'journal_meta', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }

        if (!Schema::hasTable('tag_transaction_journal')) {
            try {
                Schema::create(
                    'tag_transaction_journal',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->integer('tag_id', false, true);
                        $table->integer('transaction_journal_id', false, true);
                        $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
                        $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');

                        // unique combi:
                        $table->unique(['tag_id', 'transaction_journal_id']);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'tag_transaction_journal', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }

        if (!Schema::hasTable('budget_transaction_journal')) {
            try {
                Schema::create(
                    'budget_transaction_journal',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->integer('budget_id', false, true);
                        $table->integer('transaction_journal_id', false, true);
                        $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
                        $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'budget_transaction_journal', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }

        if (!Schema::hasTable('category_transaction_journal')) {
            try {
                Schema::create(
                    'category_transaction_journal',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->integer('category_id', false, true);
                        $table->integer('transaction_journal_id', false, true);
                        $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
                        $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'category_transaction_journal', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }

        if (!Schema::hasTable('piggy_bank_events')) {
            try {
                Schema::create(
                    'piggy_bank_events',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->integer('piggy_bank_id', false, true);
                        $table->integer('transaction_journal_id', false, true)->nullable();
                        $table->date('date');
                        $table->decimal('amount', 32, 12);

                        $table->foreign('piggy_bank_id')->references('id')->on('piggy_banks')->onDelete('cascade');
                        $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('set null');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'piggy_bank_events', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }

        if (!Schema::hasTable('transactions')) {
            try {
                Schema::create(
                    'transactions',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->integer('account_id', false, true);
                        $table->integer('transaction_journal_id', false, true);
                        $table->string('description', 1024)->nullable();
                        $table->decimal('amount', 32, 12);

                        $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
                        $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'transactions', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }

        if (!Schema::hasTable('budget_transaction')) {
            try {
                Schema::create(
                    'budget_transaction',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->integer('budget_id', false, true);
                        $table->integer('transaction_id', false, true);

                        $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
                        $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'budget_transaction', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }

        if (!Schema::hasTable('category_transaction')) {
            try {
                Schema::create(
                    'category_transaction',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->integer('category_id', false, true);
                        $table->integer('transaction_id', false, true);

                        $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
                        $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'category_transaction', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }
    }
}
