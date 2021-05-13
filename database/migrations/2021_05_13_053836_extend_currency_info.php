<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class ExtendCurrencyInfo
 */
class ExtendCurrencyInfo extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'transaction_currencies', function (Blueprint $table) {
            $table->string('code', 51)->change();
            $table->string('symbol', 51)->change();
        }
        );
    }
}
