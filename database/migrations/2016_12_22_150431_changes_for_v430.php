<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class ChangesForV430
 */
class ChangesForV430 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('available_budgets', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('user_id', false, true);
            $table->integer('transaction_currency_id', false, true);
            $table->decimal('amount', 14, 4);
            $table->date('start_date');
            $table->date('end_date');


            $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('available_budgets');
    }
}
