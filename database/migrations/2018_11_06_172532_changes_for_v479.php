<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV479
 */
class ChangesForV479 extends Migration
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
            'transaction_currencies',
            function (Blueprint $table) {
                $table->boolean('enabled')->default(0)->after('deleted_at');
            }
        );
    }
}
