<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class ChangesForV550b2
 */
class ChangesForV550b2 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table(
            'recurrences_transactions', function (Blueprint $table) {

            $table->dropForeign('type_foreign');
            $table->dropColumn('transaction_type_id');

        }
        );
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // expand recurrence transaction table
        Schema::table(
            'recurrences_transactions', function (Blueprint $table) {
            $table->integer('transaction_type_id', false, true)->nullable()->after('transaction_currency_id');
            $table->foreign('transaction_type_id', 'type_foreign')->references('id')->on('transaction_types')->onDelete('set null');
        }
        );
    }
}
