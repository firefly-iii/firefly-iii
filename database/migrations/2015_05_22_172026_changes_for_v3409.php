<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class ChangesForV3409
 */
class ChangesForV3409 extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // remove decryption, but this will destroy amounts.

        Schema::table(
            'accounts', function (Blueprint $table) {
            $table->dropColumn('virtual_balance_encrypted');
        }
        );

        Schema::table(
            'bills', function (Blueprint $table) {
            $table->dropColumn('amount_min_encrypted');
            $table->dropColumn('amount_max_encrypted');
        }
        );

        Schema::table(
            'budget_limits', function (Blueprint $table) {

            $table->dropColumn('amount_encrypted');
        }
        );
        Schema::table(
            'limit_repetitions', function (Blueprint $table) {
            $table->dropColumn('amount_encrypted');
        }
        );
        Schema::table(
            'piggy_bank_events', function (Blueprint $table) {
            $table->dropColumn('amount_encrypted');
        }
        );
        Schema::table(
            'piggy_bank_repetitions', function (Blueprint $table) {
            $table->dropColumn('currentamount_encrypted');
        }
        );

        Schema::table(
            'piggy_banks', function (Blueprint $table) {
            $table->dropColumn('targetamount_encrypted');
        }
        );
        Schema::table(
            'preferences', function (Blueprint $table) {
            $table->dropColumn('name_encrypted');
            $table->dropColumn('data_encrypted');
        }
        );

        Schema::table(
            'transactions', function (Blueprint $table) {
            $table->dropColumn('amount_encrypted');
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
        // encrypt account virtual balance:
        Schema::table(
            'accounts', function (Blueprint $table) {
            $table->string('virtual_balance_encrypted')->nullable()->after('virtual_balance');
        }
        );

        // encrypt bill amount_min and amount_max:
        Schema::table(
            'bills', function (Blueprint $table) {
            $table->string('amount_min_encrypted')->nullable()->after('amount_min');
            $table->string('amount_max_encrypted')->nullable()->after('amount_max');
        }
        );

        // encrypt budget limit amount
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->string('amount_encrypted')->nullable()->after('amount');
        }
        );
        // encrypt limit repetition amount
        Schema::table(
            'limit_repetitions', function (Blueprint $table) {
            $table->string('amount_encrypted')->nullable()->after('amount');
        }
        );
        // encrypt piggy bank event amount
        Schema::table(
            'piggy_bank_events', function (Blueprint $table) {
            $table->string('amount_encrypted')->nullable()->after('amount');
        }
        );
        // encrypt piggy bank repetition currentamount
        Schema::table(
            'piggy_bank_repetitions', function (Blueprint $table) {
            $table->string('currentamount_encrypted')->nullable()->after('currentamount');
        }
        );

        // encrypt piggy bank targetamount
        Schema::table(
            'piggy_banks', function (Blueprint $table) {
            $table->string('targetamount_encrypted')->nullable()->after('targetamount');
        }
        );
        // encrypt preference name (add field)
        // encrypt preference data (add field)
        Schema::table(
            'preferences', function (Blueprint $table) {
            $table->smallInteger('name_encrypted', false, true)->default(0)->after('name');
            $table->smallInteger('data_encrypted', false, true)->default(0)->after('data');
        }
        );

        // encrypt transaction amount
        Schema::table(
            'transactions', function (Blueprint $table) {
            $table->string('amount_encrypted')->nullable()->after('amount');
        }
        );

    }

}
