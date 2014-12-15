<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class ChangesForV321
 */
class ChangesForV321 extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // rename tables back to original name.
        Schema::rename('budget_limits', 'limits');
        Schema::rename('piggy_bank_events', 'piggybank_events');

        // rename column in "limit_repetitions" back to the original name
        Schema::table(
            'limit_repetitions', function (Blueprint $table) {
            $table->renameColumn('budget_limit_id', 'limit_id');
        }
        );

        // create column in "transactions"
        // create foreign key in "transactions"
        // TODO skipped because not supported in SQLite
        Schema::table(
            'transactions', function (Blueprint $table) {
            #$table->integer('piggybank_id')->nullable()->unsigned();
            #$table->foreign('piggybank_id')->references('id')->on('piggybanks')->onDelete('set null');
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
        // rename tables.
        Schema::rename('limits', 'budget_limits');
        Schema::rename('piggybank_events', 'piggy_bank_events');

        // rename column in "limit_repetitions"
        Schema::table(
            'limit_repetitions', function (Blueprint $table) {
            $table->renameColumn('limit_id', 'budget_limit_id');
        }
        );

        // drop foreign key in "transactions"
        // drop column in "transactions"
        // TODO skipped because not supported in SQLite
        Schema::table(
            'transactions', function (Blueprint $table) {
            #$table->dropForeign('transactions_piggybank_id_foreign');
            #$table->dropIndex('transactions_piggybank_id_foreign');
            #$table->dropColumn('piggybank_id');
        }
        );


    }

}
