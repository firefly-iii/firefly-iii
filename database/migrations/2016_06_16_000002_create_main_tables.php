<?php
/**
 * 2016_06_16_000002_create_main_tables.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */
declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateMainTables
 */
class CreateMainTables extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::drop('account_meta');
        Schema::drop('piggy_bank_repetitions');
        Schema::drop('attachments');
        Schema::drop('limit_repetitions');
        Schema::drop('budget_limits');
        Schema::drop('export_jobs');
        Schema::drop('import_jobs');
        Schema::drop('preferences');
        Schema::drop('role_user');
        Schema::drop('rule_actions');
        Schema::drop('rule_triggers');
        Schema::drop('rules');
        Schema::drop('rule_groups');
        Schema::drop('category_transaction');
        Schema::drop('budget_transaction');
        Schema::drop('transactions');
        Schema::drop('piggy_bank_events');
        Schema::drop('piggy_banks');
        Schema::drop('accounts');
        Schema::drop('category_transaction_journal');
        Schema::drop('budget_transaction_journal');
        Schema::drop('categories');
        Schema::drop('budgets');
        Schema::drop('tag_transaction_journal');
        Schema::drop('tags');
        Schema::drop('journal_meta');
        Schema::drop('transaction_journals');
        Schema::drop('bills');
    }

    /**
     * Run the migrations.
     */
    public function up()
    {
        //
        $this->createAccountTables();
        $this->createPiggyBanksTable();
        $this->createAttachmentsTable();
        $this->createBillsTable();
        $this->createBudgetTables();
        $this->createCategoriesTable();
        $this->createExportJobsTable();
        $this->createPreferencesTable();
        $this->createRoleTable();
        $this->createRuleTables();
        $this->createTagsTable();
        $this->createTransactionTables();
    }

    /**
     *
     */
    private function createAccountTables()
    {
        if (!Schema::hasTable('accounts')) {
            Schema::create(
                'accounts', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->integer('user_id', false, true);
                $table->integer('account_type_id', false, true);
                $table->string('name', 1024);
                $table->decimal('virtual_balance', 10, 4);
                $table->string('iban', 255)->nullable();

                $table->boolean('active')->default(1);
                $table->boolean('encrypted')->default(0);

                // link user id to users table
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

                // link account type id to account types table
                $table->foreign('account_type_id')->references('id')->on('account_types')->onDelete('cascade');
            }
            );
        }

        if (!Schema::hasTable('account_meta')) {
            Schema::create(
                'account_meta', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->integer('account_id', false, true);
                $table->string('name');
                $table->text('data');

                // link account id to accounts:
                $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            }
            );
        }
    }

    /**
     *
     */
    private function createAttachmentsTable()
    {

        if (!Schema::hasTable('attachments')) {
            Schema::create(
                'attachments', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->integer('user_id', false, true);
                $table->integer('attachable_id', false, true);
                $table->string('attachable_type', 255);
                $table->string('md5', 32);
                $table->string('filename', 1024);
                $table->string('title', 1024);
                $table->text('description');
                $table->text('notes');
                $table->string('mime', 200);
                $table->integer('size', false, true);
                $table->boolean('uploaded')->default(1);

                // link user id to users table
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');


            }
            );
        }
    }

    /**
     *
     */
    private function createBillsTable()
    {
        if (!Schema::hasTable('bills')) {
            Schema::create(
                'bills', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->integer('user_id', false, true);
                $table->string('name', 1024);
                $table->string('match', 1024);
                $table->decimal('amount_min', 10, 4);
                $table->decimal('amount_max', 10, 4);
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
        }
    }

    /**
     *
     */
    private function createBudgetTables()
    {


        if (!Schema::hasTable('budgets')) {
            Schema::create(
                'budgets', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->integer('user_id', false, true);
                $table->string('name', 1024);
                $table->boolean('active')->default(1);
                $table->boolean('encrypted')->default(0);

                // link user id to users table
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');


            }
            );
        }
        if (!Schema::hasTable('budget_limits')) {
            Schema::create(
                'budget_limits', function (Blueprint $table) {

                $table->increments('id');
                $table->timestamps();
                $table->integer('budget_id', false, true);
                $table->date('startdate');
                $table->decimal('amount', 10, 4);
                $table->string('repeat_freq', 30);
                $table->boolean('repeats')->default(0);

                // link budget id to budgets table
                $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');

            }
            );
        }
        if (!Schema::hasTable('limit_repetitions')) {
            Schema::create(
                'limit_repetitions', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->integer('budget_limit_id', false, true);
                $table->date('startdate');
                $table->date('enddate');
                $table->decimal('amount', 10, 4);

                // link budget limit id to budget_limitss table
                $table->foreign('budget_limit_id')->references('id')->on('budget_limits')->onDelete('cascade');
            }
            );
        }
    }

    /**
     *
     */
    private function createCategoriesTable()
    {
        if (!Schema::hasTable('categories')) {
            Schema::create(
                'categories', function (Blueprint $table) {

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
        }
    }

    /**
     *
     */
    private function createExportJobsTable()
    {
        if (!Schema::hasTable('export_jobs')) {
            Schema::create(
                'export_jobs', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->integer('user_id', false, true);
                $table->string('key', 12);
                $table->string('status', 255);
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
            );
        }

        if (!Schema::hasTable('import_jobs')) {
            Schema::create(
                'import_jobs', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->integer('user_id')->unsigned();
                $table->string('key', 12)->unique();
                $table->string('file_type', 12);
                $table->string('status', 45);
                $table->text('configuration');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
            );
        }

    }

    /**
     *
     */
    private function createPiggyBanksTable()
    {
        if (!Schema::hasTable('piggy_banks')) {
            Schema::create(
                'piggy_banks', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->integer('account_id', false, true);
                $table->string('name', 1024);
                $table->decimal('targetamount', 10, 4);
                $table->date('startdate')->nullable();
                $table->date('targetdate')->nullable();
                $table->integer('order', false, true);
                $table->boolean('active')->default(0);
                $table->boolean('encrypted')->default(1);

                // link to account_id to accounts
                $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            }
            );
        }

        if (!Schema::hasTable('piggy_bank_repetitions')) {
            Schema::create(
                'piggy_bank_repetitions', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->integer('piggy_bank_id', false, true);
                $table->date('startdate')->nullable();
                $table->date('targetdate')->nullable();
                $table->decimal('currentamount', 10, 4);

                $table->foreign('piggy_bank_id')->references('id')->on('piggy_banks')->onDelete('cascade');

            }
            );
        }

    }

    /**
     *
     */
    private function createPreferencesTable()
    {
        if (!Schema::hasTable('preferences')) {
            Schema::create(
                'preferences', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->integer('user_id', false, true);
                $table->string('name', 1024);
                $table->text('data');

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
            );
        }
    }

    /**
     *
     */
    private function createRoleTable()
    {

        if (!Schema::hasTable('role_user')) {
            Schema::create(
                'role_user', function (Blueprint $table) {
                $table->integer('user_id', false, true);
                $table->integer('role_id', false, true);

                $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('role_id')->references('id')->on('roles')->onUpdate('cascade')->onDelete('cascade');

                $table->primary(['user_id', 'role_id']);

            }
            );
        }

    }

    /**
     *
     */
    private function createRuleTables()
    {
        if (!Schema::hasTable('rule_groups')) {
            Schema::create(
                'rule_groups', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->integer('user_id', false, true);
                $table->string('title', 255);
                $table->text('description');
                $table->integer('order', false, true);
                $table->boolean('active')->default(1);

                // link user id to users table
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
            );
        }
        if (!Schema::hasTable('rules')) {
            Schema::create(
                'rules', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->integer('user_id', false, true);
                $table->integer('rule_group_id', false, true);
                $table->string('title', 255);
                $table->text('description');
                $table->integer('order', false, true);
                $table->boolean('active')->default(1);
                $table->boolean('stop_processing')->default(0);

                // link user id to users table
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

                // link rule group id to rule group table
                $table->foreign('rule_group_id')->references('id')->on('rule_groups')->onDelete('cascade');
            }
            );
        }
        if (!Schema::hasTable('rule_actions')) {
            Schema::create(
                'rule_actions', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->integer('rule_id', false, true);

                $table->string('action_type', 50);
                $table->string('action_value', 255);

                $table->integer('order', false, true);
                $table->boolean('active')->default(1);
                $table->boolean('stop_processing')->default(0);


                // link rule id to rules table
                $table->foreign('rule_id')->references('id')->on('rules')->onDelete('cascade');
            }
            );
        }
        if (!Schema::hasTable('rule_triggers')) {
            Schema::create(
                'rule_triggers', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->integer('rule_id', false, true);

                $table->string('trigger_type', 50);
                $table->string('trigger_value', 255);

                $table->integer('order', false, true);
                $table->boolean('active')->default(1);
                $table->boolean('stop_processing')->default(0);


                // link rule id to rules table
                $table->foreign('rule_id')->references('id')->on('rules')->onDelete('cascade');
            }
            );
        }
    }

    /**
     *
     */
    private function createTagsTable()
    {
        if (!Schema::hasTable('tags')) {
            Schema::create(
                'tags', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->integer('user_id', false, true);

                $table->string('tag', 1024);
                $table->string('tagMode', 1024);
                $table->date('date')->nullable();
                $table->text('description')->nullable();
                $table->decimal('latitude', 18, 12)->nullable();
                $table->decimal('longitude', 18, 12)->nullable();
                $table->boolean('zoomLevel')->nullable();

                // link user id to users table
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            }
            );
        }
    }

    /**
     *
     */
    private function createTransactionTables()
    {
        if (!Schema::hasTable('transaction_journals')) {
            Schema::create(
                'transaction_journals', function (Blueprint $table) {
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

                $table->integer('order', false, true);
                $table->integer('tag_count', false, true);

                $table->boolean('encrypted')->default(1);
                $table->boolean('completed')->default(1);

                // links to other tables:
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('transaction_type_id')->references('id')->on('transaction_types')->onDelete('cascade');
                $table->foreign('bill_id')->references('id')->on('bills')->onDelete('set null');
                $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
            }
            );
        }

        if (!Schema::hasTable('journal_meta')) {
            Schema::create(
                'journal_meta', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->integer('transaction_journal_id', false, true);
                $table->string('name', 255);
                $table->text('data');
                $table->string('hash', 64);

                $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');
            }
            );
        }

        if (!Schema::hasTable('tag_transaction_journal')) {
            Schema::create(
                'tag_transaction_journal', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('tag_id', false, true);
                $table->integer('transaction_journal_id', false, true);

                $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
                $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');


            }
            );
        }

        if (!Schema::hasTable('budget_transaction_journal')) {
            Schema::create(
                'budget_transaction_journal', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('budget_id', false, true);
                $table->integer('transaction_journal_id', false, true);

                $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
                $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');
            }
            );
        }

        if (!Schema::hasTable('category_transaction_journal')) {
            Schema::create(
                'category_transaction_journal', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('category_id', false, true);
                $table->integer('transaction_journal_id', false, true);

                $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
                $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');
            }
            );
        }


        if (!Schema::hasTable('piggy_bank_events')) {
            Schema::create(
                'piggy_bank_events', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->integer('piggy_bank_id', false, true);
                $table->integer('transaction_journal_id', false, true)->nullable();
                $table->date('date');
                $table->decimal('amount', 10, 4);

                $table->foreign('piggy_bank_id')->references('id')->on('piggy_banks')->onDelete('cascade');
                $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('set null');
            }
            );
        }

        if (!Schema::hasTable('transactions')) {
            Schema::create(
                'transactions', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->integer('account_id', false, true);
                $table->integer('transaction_journal_id', false, true);
                $table->string('description', 1024)->nullable();
                $table->decimal('amount', 10, 4);

                $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
                $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');

            }
            );
        }

        if (!Schema::hasTable('budget_transaction')) {
            Schema::create(
                'budget_transaction', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('budget_id', false, true);
                $table->integer('transaction_id', false, true);

                $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
                $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            }
            );
        }

        if (!Schema::hasTable('category_transaction')) {
            Schema::create(
                'category_transaction', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('category_id', false, true);
                $table->integer('transaction_id', false, true);

                $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
                $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            }
            );
        }
    }
}
