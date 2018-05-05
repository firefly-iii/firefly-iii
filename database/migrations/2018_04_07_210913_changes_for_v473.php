<?php
declare(strict_types=1);

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
                $table->integer('transaction_currency_id', false, true)->nullable()->after('user_id');
                $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('set null');
            }
        );
        Schema::table(
            'rules',
            function (Blueprint $table) {
                $table->boolean('strict')->default(true);
            }
        );
    }
}
