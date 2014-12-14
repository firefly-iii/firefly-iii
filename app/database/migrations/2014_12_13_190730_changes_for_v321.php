<?php

use Illuminate\Database\Migrations\Migration;

/**
 * SuppressWarnings(PHPMD.ShortMethodName)
 *
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
        Schema::rename('budget_limits', 'limits');
        Schema::rename('piggy_bank_events', 'piggybank_events');
        DB::update(DB::raw('ALTER TABLE `limit_repetitions` ALGORITHM=INPLACE, CHANGE `budget_limit_id` `limit_id` INT UNSIGNED NOT NULL'));
        DB::update(DB::Raw('ALTER TABLE `transactions` ADD `piggybank_id` INT(10) UNSIGNED DEFAULT NULL AFTER `account_id`;'));
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('limits', 'budget_limits');
        DB::update(DB::raw('ALTER TABLE `limit_repetitions` ALGORITHM=INPLACE, CHANGE `limit_id` `budget_limit_id` INT UNSIGNED NOT NULL'));
        DB::update(DB::Raw('ALTER TABLE `transactions` DROP `piggybank_id`'));
        Schema::rename('piggybank_events', 'piggy_bank_events');

    }

}
