<?php
declare(strict_types = 1);


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;


/**
 * Class ChangesForV383
 */
class ChangesForV383 extends Migration
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
        // extend journal meta
        Schema::table(
            'journal_meta', function (Blueprint $table) {
            $table->string('hash', 64)->nullable();
        }
        );
    }
}
