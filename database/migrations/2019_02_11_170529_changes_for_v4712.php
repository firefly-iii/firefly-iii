<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class ChangesForV4712
 */
class ChangesForV4712 extends Migration
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
        /**
         * In 4.7.11, I changed the date field to a "datetimetz" field. This wreaks havoc
         * because apparently MySQL is not actually capable of handling multiple time zones,
         * only having a server wide time zone setting. Actual database schemes like Postgres
         * handle this just fine but the combination is unpredictable. So we go back to
         * datetime (without a time zone) for all database engines because MySQL refuses to play
         * nice.
         */
        Schema::table(
            'transaction_journals', function (Blueprint $table) {
            $table->dateTime('date')->change();
        }
        );
    }
}
