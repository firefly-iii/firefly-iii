<?php
declare(strict_types = 1);


use Illuminate\Database\Migrations\Migration;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class ChangesForV3310b
 */
class ChangesForV3310b extends Migration
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
        // set all current entries to be "balance"
        DB::table('transaction_groups')->update(['relation' => 'balance']);
    }

}
