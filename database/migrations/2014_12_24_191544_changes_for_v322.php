<?php
declare(strict_types = 1);


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 *
 * Class ChangesForV322
 */
class ChangesForV322 extends Migration
{
    /**
     *
     */
    public function down()
    {
        // rename tables:
        Schema::rename('piggy_bank_repetitions', 'piggybank_repetitions');
        Schema::rename('piggy_banks', 'piggybanks');

        // rename fields
        Schema::table(
            'piggy_bank_events', function (Blueprint $table) {
            $table->dropForeign('piggy_bank_events_piggy_bank_id_foreign');
            $table->renameColumn('piggy_bank_id', 'piggybank_id');
            $table->foreign('piggybank_id')->references('id')->on('piggybanks')->onDelete('cascade');
        }
        );

        Schema::table(
            'piggybank_repetitions', function (Blueprint $table) {
            $table->dropForeign('piggy_bank_repetitions_piggy_bank_id_foreign');
            $table->renameColumn('piggy_bank_id', 'piggybank_id');
            $table->foreign('piggybank_id')->references('id')->on('piggybanks')->onDelete('cascade');
        }
        );

        // remove soft delete to piggy banks
        Schema::table(
            'piggybanks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        }
        );

        // drop keys from bills (foreign bills_uid_for and unique uid_name_unique)
        Schema::table(
            'bills', function (Blueprint $table) {
            $table->dropForeign('bills_uid_for');
            $table->dropUnique('uid_name_unique');
        }
        );
        // drop foreign key from transaction_journals (bill_id_foreign)
        Schema::table(
            'transaction_journals', function (Blueprint $table) {
            $table->dropForeign('bill_id_foreign');

        }
        );

        // drop unique constraint from budget_limits:
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->dropForeign('bid_foreign');
            $table->dropUnique('unique_bl_combi');
            $table->foreign('budget_id', 'bid_foreign')->references('id')->on('budgets')->onDelete('cascade');
        }
        );

        // rename bills to recurring_transactions
        Schema::rename('bills', 'recurring_transactions');
        // recreate foreign key recurring_transactions_user_id_foreign in recurring_transactions
        // recreate unique recurring_transactions_user_id_name_unique in recurring_transactions
        Schema::table(
            'recurring_transactions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'name']);
        }
        );

        // rename bill_id to recurring_transaction_id
        // recreate foreign transaction_journals_recurring_transaction_id_foreign in transaction_journals
        Schema::table(
            'transaction_journals', function (Blueprint $table) {
            $table->renameColumn('bill_id', 'recurring_transaction_id');
            $table->foreign('recurring_transaction_id')->references('id')->on('recurring_transactions')->onDelete('set null');
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
        // rename tables:
        Schema::rename('piggybank_repetitions', 'piggy_bank_repetitions');
        Schema::rename('piggybanks', 'piggy_banks');

        // recreate it the correct way:
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->unique(['budget_id', 'startdate', 'repeat_freq'], 'unique_bl_combi');
        }
        );

        // rename fields
        Schema::table(
            'piggy_bank_events', function (Blueprint $table) {
            $table->dropForeign('piggybank_events_piggybank_id_foreign');
            $table->renameColumn('piggybank_id', 'piggy_bank_id');
            $table->foreign('piggy_bank_id')->references('id')->on('piggy_banks')->onDelete('cascade');
        }
        );

        Schema::table(
            'piggy_bank_repetitions', function (Blueprint $table) {
            $table->dropForeign('piggybank_repetitions_piggybank_id_foreign');
            $table->renameColumn('piggybank_id', 'piggy_bank_id');
            $table->foreign('piggy_bank_id')->references('id')->on('piggy_banks')->onDelete('cascade');
        }
        );

        // add soft delete to piggy banks
        Schema::table(
            'piggy_banks', function (Blueprint $table) {
            $table->softDeletes();
        }
        );

        // rename everything related to recurring transactions, aka bills:
        Schema::table(
            'transaction_journals', function (Blueprint $table) {


            // drop relation
            $table->dropForeign('transaction_journals_recurring_transaction_id_foreign');
            // rename column
            $table->renameColumn('recurring_transaction_id', 'bill_id');

        }
        );

        Schema::table(
            'recurring_transactions', function (Blueprint $table) {
            $table->dropForeign('recurring_transactions_user_id_foreign');
            $table->dropUnique('recurring_transactions_user_id_name_unique');
        }
        );
        // rename table:
        Schema::rename('recurring_transactions', 'bills');

        // recreate foreign relation:
        Schema::table(
            'transaction_journals', function (Blueprint $table) {
            $table->foreign('bill_id', 'bill_id_foreign')->references('id')->on('bills')->onDelete('set null');
        }
        );

        // recreate more foreign relations.
        Schema::table(
            'bills', function (Blueprint $table) {
            // connect user id to users
            $table->foreign('user_id', 'bills_uid_for')->references('id')->on('users')->onDelete('cascade');

            // for a user, the name must be unique
            $table->unique(['user_id', 'name'], 'uid_name_unique');
        }
        );


    }

}
