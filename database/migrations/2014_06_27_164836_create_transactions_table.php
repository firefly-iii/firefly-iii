<?php
declare(strict_types = 1);



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class CreateTransactionsTable
 *
 */
class CreateTransactionsTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('transactions');
    }

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
            $table->softDeletes();
            $table->integer('account_id')->unsigned();
            $table->integer('piggybank_id')->nullable()->unsigned();
            $table->integer('transaction_journal_id')->unsigned();
            $table->string('description', 255)->nullable();
            $table->decimal('amount', 10, 2);

            // connect account id:
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

            // connect piggy banks
            $table->foreign('piggybank_id')->references('id')->on('piggybanks')->onDelete('set null');

            // connect transactions to transaction journals
            $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');


        }
        );
    }

}
