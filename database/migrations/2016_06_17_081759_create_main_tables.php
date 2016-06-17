<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMainTables extends Migration
{
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
        // $this->createImportJobsTable();
        $this->createPreferencesTable();
        $this->createRoleTable();
        $this->createRuleTables();
        //        $this->createTagsTable();
        //        $this->createTransactionTables();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        //
        Schema::drop('account_meta');
        Schema::drop('accounts');

        Schema::drop('piggy_bank_repetitions');
        Schema::drop('piggy_banks');

        Schema::drop('attachments');

        Schema::drop('bills');

        Schema::drop('limit_repetitions');
        Schema::drop('budget_limits');
        Schema::drop('budgets');

        Schema::drop('categories');

        Schema::drop('export_jobs');

        Schema::drop('preferences');

        Schema::drop('role_user');

        Schema::drop('rule_actions');
        Schema::drop('rule_triggers');
        Schema::drop('rules');
        Schema::drop('rule_groups');


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
                $table->string('iban', 255);

                $table->tinyInteger('active', false, true)->default(1);
                $table->tinyInteger('encrypted', false, true)->default(0);

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
                $table->string('attachable_name', 255);
                $table->string('md5', 32);
                $table->string('filename', 1024);
                $table->string('title', 1024);
                $table->text('description');
                $table->text('notes');
                $table->string('mime', 200);
                $table->integer('size', false, true);
                $table->tinyInteger('uploaded', false, true)->default(1);

                // link user id to users table
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');


            }
            );
        }
    }

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
                $table->tinyInteger('active', false, true)->default(1);
                $table->tinyInteger('name_encrypted', false, true)->default(0);
                $table->tinyInteger('match_encrypted', false, true)->default(0);

                // link user id to users table
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
            );
        }
    }

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
                $table->tinyInteger('active', false, true)->default(1);
                $table->tinyInteger('encrypted', false, true)->default(0);

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
                $table->tinyInteger('repeats', false, true)->default(0);

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
                $table->tinyInteger('encrypted', false, true)->default(0);

                // link user id to users table
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
            );
        }
    }

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

                // link user id to users table
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
            );
        }


    }

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
                $table->date('startdate');
                $table->date('targetdate');
                $table->integer('order', false, true);
                $table->tinyInteger('active', false, true)->default(0);

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
                $table->date('startdate');
                $table->date('targetdate');
                $table->decimal('currentamount', 10, 4);

                // link to account_id to accounts
                $table->foreign('piggy_bank_id')->references('id')->on('piggy_banks')->onDelete('cascade');

            }
            );
        }

    }

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

                // link user id to users table
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
            );
        }
    }

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
                $table->tinyInteger('active', false, true)->default(1);

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
                $table->tinyInteger('active', false, true)->default(1);
                $table->tinyInteger('stop_processing', false, true)->default(0);

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
                $table->tinyInteger('active', false, true)->default(1);
                $table->tinyInteger('stop_processing', false, true)->default(0);



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
                $table->tinyInteger('active', false, true)->default(1);
                $table->tinyInteger('stop_processing', false, true)->default(0);



                // link rule id to rules table
                $table->foreign('rule_id')->references('id')->on('rules')->onDelete('cascade');
            }
            );
        }
    }
}
