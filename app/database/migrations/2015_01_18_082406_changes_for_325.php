<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 * @SuppressWarnings("MethodLength") // I don't mind this in case of migrations.
 *
 * Class ChangesForV325
 */
class ChangesFor325 extends Migration
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
        //

        // delete an old index:
        try {
            Schema::table(
                'budget_limits', function (Blueprint $table) {
                //$table->dropIndex('unique_ci_combo');
                $table->dropUnique('unique_ci_combi');

            }
            );
        } catch (QueryException $e) {
            // don't care.
        } catch (PDOException $e) {
            // don't care.
        } catch (\Exception $e) {
            // don't care either.
        }

        // allow journal descriptions to be encrypted.
        Schema::table(
            'transaction_journals', function (Blueprint $table) {
            $table->boolean('encrypted');

        }
        );
        DB::update('ALTER TABLE `transaction_journals` MODIFY `description` VARCHAR(1024)');

    }

}
