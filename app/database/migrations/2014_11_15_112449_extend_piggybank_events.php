<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class ExtendPiggybankEvents extends Migration
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
            'piggybank_events', function (Blueprint $table) {
                $table->integer('transaction_journal_id')->unsigned()->nullable();
                $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('set null');
            }
        );
    }

}
