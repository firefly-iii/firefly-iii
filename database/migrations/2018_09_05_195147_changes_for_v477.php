<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 *
 * Class ChangesForV477
 */
class ChangesForV477 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        //
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table(
            'budget_limits',
            function (Blueprint $table) {
                $table->integer('transaction_currency_id', false, true)->nullable()->after('budget_id');
                $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('set null');
            }
        );
    }
}
