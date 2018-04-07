<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class ChangesForV473
 */
class ChangesForV473 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'bills',
            function (Blueprint $table) {
                $table->integer('currency_id', false, true)->nullable()->after('user_id');
                $table->foreign('currency_id')->references('id')->on('transaction_currencies')->onDelete('set null');
            }
        );
    }
}
