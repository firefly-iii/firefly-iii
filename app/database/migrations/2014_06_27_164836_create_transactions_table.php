<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateTransactionsTable
 *
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class CreateTransactionsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'transactions', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->integer('account_id')->unsigned();
                $table->integer('transaction_journal_id')->unsigned();
                $table->string('description', 255)->nullable();
                $table->decimal('amount', 10, 2);

                // connect transactions to transaction journals
                $table->foreign('transaction_journal_id')
                    ->references('id')->on('transaction_journals')
                    ->onDelete('cascade');

                // connect account id:
                $table->foreign('account_id')
                    ->references('id')->on('accounts')
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
        Schema::drop('transactions');
    }

}
