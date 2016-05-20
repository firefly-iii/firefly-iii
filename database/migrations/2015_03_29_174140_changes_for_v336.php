<?php
declare(strict_types = 1);


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
 *
 * Class ChangesForV336
 */
class ChangesForV336 extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        /**
         * ACCOUNTS
         */
        // unchange field to be encryptable.
        Schema::table(
            'accounts', function (Blueprint $table) {
            // drop foreign key:
            $table->dropForeign('account_user_id');

        }
        );


        Schema::table(
            'accounts', function (Blueprint $table) {
            $table->string('name', 255)->change();
            $table->dropColumn('virtual_balance');

            // recreate foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // recreate unique:
            $table->unique(['user_id', 'account_type_id', 'name']);
        }
        );


        /**
         * BILLS
         */
        // change field to be cryptable.
        Schema::table(
            'bills', function (Blueprint $table) {
            // drop foreign key:
            $table->dropForeign('bill_user_id');

            // drop unique:
            $table->dropUnique('bill_user_id');
        }
        );
        //
        Schema::table(
            'bills', function (Blueprint $table) {
            // raw query:

            DB::insert('ALTER TABLE `bills` CHANGE `name` `name` varchar(255) NOT NULL');
            DB::insert('ALTER TABLE `bills` CHANGE `match` `match` varchar(255) NOT NULL');
            $table->foreign('user_id', 'bills_uid_for')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'name'], 'uid_name_unique');
        }
        );

        // remove a long forgotten index:
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->dropUnique('unique_limit');
        }
        );

    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        /**
         * ACCOUNTS
         */
        // change field to be cryptable.
        Schema::table(
            'accounts', function (Blueprint $table) {
            // drop foreign key:
            $table->dropForeign('accounts_user_id_foreign');

            // drop unique:
            $table->dropUnique('accounts_user_id_account_type_id_name_unique');
        }
        );

        Schema::table(
            'accounts', function (Blueprint $table) {
            $table->text('name')->change();
            $table->decimal('virtual_balance', 10, 2)->default(0);
            $table->foreign('user_id', 'account_user_id')->references('id')->on('users')->onDelete('cascade');
        }
        );

        /**
         * BUDGETS
         */
        // add active/inactive and encrypt.
        Schema::table(
            'budgets', function (Blueprint $table) {
            $table->smallInteger('active', false, true)->default(1);
            $table->smallInteger('encrypted', false, true)->default(0);

            // drop foreign key:
            $table->dropForeign('budgets_user_id_foreign');

            // drop unique:
            $table->dropUnique('budgets_user_id_name_unique');

        }
        );
        Schema::table(
            'budgets', function (Blueprint $table) {
            $table->text('name')->change();
            $table->foreign('user_id', 'budget_user_id')->references('id')->on('users')->onDelete('cascade');
        }
        );

        // reinstate a long forgotten index:
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->unique(['budget_id', 'startdate'], 'unique_limit');
        }
        );


        /**
         * BILLS
         */
        // change field to be cryptable.
        Schema::table(
            'bills', function (Blueprint $table) {
            // drop foreign key:
            $table->dropForeign('bills_uid_for');

            // drop unique:
            $table->dropUnique('uid_name_unique');
        }
        );

        Schema::table(
            'bills', function (Blueprint $table) {
            // raw query:
            try {
                DB::insert('ALTER TABLE `bills` CHANGE `name` `name` TEXT NOT NULL');
            } catch (PDOException $e) {
                // don't care.
            }
            try {
                DB::insert('ALTER TABLE `bills` CHANGE `match` `match` TEXT NOT NULL');
            } catch (PDOException $e) {
                // don't care.
            }
            $table->smallInteger('name_encrypted', false, true)->default(0);
            $table->smallInteger('match_encrypted', false, true)->default(0);
            $table->foreign('user_id', 'bill_user_id')->references('id')->on('users')->onDelete('cascade');
        }
        );

        /**
         * CATEGORIES
         */
        Schema::table(
            'categories', function (Blueprint $table) {
            $table->smallInteger('encrypted', false, true)->default(0);

            // drop foreign key:
            $table->dropForeign('categories_user_id_foreign');

            // drop unique:
            $table->dropUnique('categories_user_id_name_unique');

        }
        );
        Schema::table(
            'categories', function (Blueprint $table) {
            $table->text('name')->change();
            $table->foreign('user_id', 'category_user_id')->references('id')->on('users')->onDelete('cascade');
        }
        );

        /**
         * PIGGY BANKS
         */
        Schema::table(
            'piggy_banks', function (Blueprint $table) {
            $table->smallInteger('encrypted', false, true)->default(0);

            // drop foreign:
            $table->dropForeign('piggybanks_account_id_foreign');

            // drop unique:
            $table->dropUnique('piggybanks_account_id_name_unique');

        }
        );
        Schema::table(
            'piggy_banks', function (Blueprint $table) {
            try {
                DB::insert('ALTER TABLE `piggy_banks` CHANGE `name` `name` TEXT NOT NULL');
            } catch (PDOException $e) {
                // don't care.
            }
            $table->dropColumn(['repeats', 'rep_length', 'rep_every', 'rep_times']);

            // create index again:
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        }
        );

        /**
         * REMINDERS
         */
        Schema::table(
            'reminders', function (Blueprint $table) {
            $table->smallInteger('encrypted', false, true)->default(0);


        }
        );

    }

}
