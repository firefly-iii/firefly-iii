<?php

use Illuminate\Database\Migrations\Migration;

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
        DB::statement('UPDATE `piggy_banks` SET `reminder_skip` = 0 WHERE `reminder_skip` IS NULL;');
        DB::statement('ALTER TABLE `piggy_banks` MODIFY `reminder_skip` SMALLINT UNSIGNED NOT NULL;');

        DB::statement('UPDATE `piggy_banks` SET `remind_me` = 0 WHERE `remind_me` IS NULL;');
        DB::statement('ALTER TABLE `piggy_banks` MODIFY `remind_me` TINYINT UNSIGNED NOT NULL;');

    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        DB::statement('ALTER TABLE `piggy_banks` MODIFY `reminder_skip` SMALLINT UNSIGNED NULL;');
        DB::statement('ALTER TABLE `piggy_banks` MODIFY `remind_me` TINYINT UNSIGNED NULL;');

        //'reminder_skip'
        //'remind_me'

    }
}
