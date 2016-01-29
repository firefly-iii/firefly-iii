<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
 *
 * Class ChangesForV325
 */
class ChangesForV325 extends Migration
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
        // delete an old index:
        try {
            Schema::table(
                'budget_limits', function (Blueprint $table) {
                $table->dropUnique('unique_ci_combi');

            }
            );
        } catch (PDOException $e) {
            // don't care.
        }

        // allow journal descriptions to be encrypted.
        Schema::table(
            'transaction_journals', function (Blueprint $table) {
            $table->boolean('encrypted')->default(0);

        }
        );
        try {
            DB::update('ALTER TABLE `transaction_journals` MODIFY `description` VARCHAR(1024)');
        } catch (PDOException $e) {
            // don't care.
        }

    }

}
