<?php
declare(strict_types=1);


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV430
 */
class ChangesForV430 extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('available_budgets');
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up()
    {
        Schema::create(
            'available_budgets', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('user_id', false, true);
            $table->integer('transaction_currency_id', false, true);
            $table->decimal('amount', 22, 12);
            $table->date('start_date');
            $table->date('end_date');


            $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        }
        );
    }
}
