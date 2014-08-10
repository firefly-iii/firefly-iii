<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateTransactionTypesTable
 *
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class CreateTransactionTypesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'transaction_types', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->string('type', 50);
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
        Schema::drop('transaction_types');
    }

}
