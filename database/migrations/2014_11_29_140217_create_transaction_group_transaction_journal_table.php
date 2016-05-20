<?php
declare(strict_types = 1);


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateTransactionGroupTransactionJournalTable
 *
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class CreateTransactionGroupTransactionJournalTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::drop('transaction_group_transaction_journal');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create(
            'transaction_group_transaction_journal', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('transaction_group_id')->unsigned();
            $table->integer('transaction_journal_id')->unsigned();

            // link to foreign tables.
            $table->foreign('transaction_group_id', 'tr_grp_id')->references('id')->on('transaction_groups')->onDelete('cascade');
            $table->foreign('transaction_journal_id', 'tr_trj_id')->references('id')->on('transaction_journals')->onDelete('cascade');

            // add unique.
            $table->unique(['transaction_group_id', 'transaction_journal_id'], 'tt_joined');
        }
        );
    }

}
