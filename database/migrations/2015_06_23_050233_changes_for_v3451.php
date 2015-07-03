<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV3451
 */
class ChangesForV3451 extends Migration
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
        Schema::table(
            'accounts', function (Blueprint $table) {
            $table->string('iban', 38)->nullable();
        }
        );

    }
}
