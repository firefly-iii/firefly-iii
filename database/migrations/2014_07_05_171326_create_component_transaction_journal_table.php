<?php
declare(strict_types = 1);


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class CreateComponentTransactionJournalTable
 *
 */
class CreateComponentTransactionJournalTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('component_transaction_journal');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'component_transaction_journal', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('component_id')->unsigned();
            $table->integer('transaction_journal_id')->unsigned();

            // link components with component_id
            $table->foreign('component_id')->references('id')->on('components')->onDelete('cascade');

            // link transaction journals with transaction_journal_id
            $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');

            // combo must be unique:
            $table->unique(['component_id', 'transaction_journal_id'], 'cid_tjid_unique');
        }
        );
    }

}
