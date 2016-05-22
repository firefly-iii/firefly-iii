<?php
declare(strict_types = 1);



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class CreateComponentTransactionTable
 *
 */
class CreateComponentTransactionTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('component_transaction');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'component_transaction', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('component_id')->unsigned();
            $table->integer('transaction_id')->unsigned();

            // connect to components
            $table->foreign('component_id')->references('id')->on('components')->onDelete('cascade');

            // connect to transactions
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');

            // combo must be unique:
            $table->unique(['component_id', 'transaction_id']);
        }
        );
    }

}
