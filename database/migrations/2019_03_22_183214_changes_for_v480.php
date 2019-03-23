<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV480
 */
class ChangesForV480 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table(
            'transaction_journals',
            function (Blueprint $table) {
                // drop transaction_group_id + foreign key.
                // cannot drop foreign keys in SQLite:
                if ('sqlite' !== config('database.default')) {
                    $table->dropForeign('transaction_journals_transaction_group_id_foreign');
                }
                $table->dropColumn('transaction_group_id');
            }
        );
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {

        Schema::table(
            'transaction_journals',
            function (Blueprint $table) {

                $table->integer('transaction_currency_id', false, true)->nullable()->change();

                // add column "group_id" after "transaction_type_id"
                $table->integer('transaction_group_id', false, true)
                      ->nullable()->default(null)->after('transaction_type_id');

                // add foreign key for "transaction_group_id"
                $table->foreign('transaction_group_id')->references('id')->on('transaction_groups')->onDelete('cascade');
            }
        );
    }
}
