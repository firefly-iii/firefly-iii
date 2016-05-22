<?php
declare(strict_types = 1);



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class CreateTransactionJournalsTable
 *
 */
class CreateTransactionJournalsTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('transaction_journals');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'transaction_journals', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('user_id')->unsigned();
            $table->integer('transaction_type_id')->unsigned();
            $table->integer('recurring_transaction_id')->unsigned()->nullable();
            $table->integer('transaction_currency_id')->unsigned();
            $table->string('description', 255)->nullable();
            $table->boolean('completed');
            $table->date('date');

            // connect users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // connect transaction journals to transaction types
            $table->foreign('transaction_type_id')->references('id')->on('transaction_types')->onDelete('cascade');

            // connect transaction journals to recurring transactions
            $table->foreign('recurring_transaction_id')->references('id')->on('recurring_transactions')->onDelete('set null');

            // connect transaction journals to transaction currencies
            $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');


        }
        );
    }

}
