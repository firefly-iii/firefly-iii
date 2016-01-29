<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV349
 *
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class ChangesForV349 extends Migration
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
        // add "blocked" to users:
        Schema::table(
            'users', function (Blueprint $table) {
            $table->boolean('blocked')->default(0);
        }
        );
    }
}
