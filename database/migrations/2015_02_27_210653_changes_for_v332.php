<?php
declare(strict_types = 1);


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class ChangesForV332
 */
class ChangesForV332 extends Migration
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
            'accounts', function (Blueprint $table) {
            $table->boolean('encrypted')->default(0);

        }
        );

        Schema::table(
            'reminders', function (Blueprint $table) {
            $table->text('metadata')->nullable();

        }
        );


    }

}
