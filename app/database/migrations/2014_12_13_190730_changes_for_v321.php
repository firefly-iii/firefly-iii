<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Class ChangesForV321
 */
class ChangesForV321 extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::update(DB::raw('RENAME TABLE `budget_limits` TO `limits`;'));
        DB::update(DB::raw('ALTER TABLE `limit_repetitions` ALGORITHM=INPLACE, CHANGE `budget_limit_id` `limit_id` INT UNSIGNED NOT NULL'));

    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::update(DB::raw('RENAME TABLE `limits` TO `budget_limits`;'));
        DB::update(DB::raw('ALTER TABLE `limit_repetitions` ALGORITHM=INPLACE, CHANGE `limit_id` `budget_limit_id` INT UNSIGNED NOT NULL'));

    }

}
