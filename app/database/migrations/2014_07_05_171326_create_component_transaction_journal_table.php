<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateComponentTransactionJournalTable
 *
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class CreateComponentTransactionJournalTable extends Migration
{

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
                $table->foreign('component_id')
                    ->references('id')->on('components')
                    ->onDelete('cascade');

                // link transaction journals with transaction_journal_id
                $table->foreign('transaction_journal_id')
                    ->references('id')->on('transaction_journals')
                    ->onDelete('cascade');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('component_transaction_journal');
    }

}
