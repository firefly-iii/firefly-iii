<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV3462
 */
class ChangesForV3462 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            'accounts', function (Blueprint $table) {
            $table->dropColumn('iban');
        }
        );
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add IBAN to accounts:
        Schema::table(
            'accounts', function (Blueprint $table) {
            $table->string('iban')->nullable();
        }
        );
    }
}
