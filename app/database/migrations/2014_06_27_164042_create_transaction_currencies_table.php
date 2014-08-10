<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateTransactionCurrenciesTable
 *
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class CreateTransactionCurrenciesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'transaction_currencies', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->string('code', 3);
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
        Schema::drop('transaction_currencies');
    }

}
