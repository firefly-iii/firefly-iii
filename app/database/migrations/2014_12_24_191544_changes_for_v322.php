<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV322
 */
class ChangesForV322 extends Migration
{
    /**
     *
     */
    public function down()
    {
        // rename tables:
        Schema::rename('piggy_bank_repetitions', 'piggybank_repetitions');
        Schema::rename('piggy_banks', 'piggybanks');

        // rename fields
        Schema::table(
            'piggy_bank_events', function (Blueprint $table) {
            $table->renameColumn('piggy_bank_id', 'piggybank_id');
        }
        );

        Schema::table(
            'piggybank_repetitions', function (Blueprint $table) {
            $table->renameColumn('piggy_bank_id', 'piggybank_id');
        }
        );

        // remove soft delete to piggy banks
        Schema::table(
            'piggybanks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        }
        );
    }


    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // rename tables:
        Schema::rename('piggybank_repetitions', 'piggy_bank_repetitions');
        Schema::rename('piggybanks', 'piggy_banks');

        // drop an invalid index.
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->dropIndex('limits_component_id_startdate_repeat_freq_unique');
        }
        );
        // recreate it the correct way:
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->unique(['budget_id', 'startdate', 'repeat_freq'], 'unique_bl_combi');
        }
        );

        // rename fields
        Schema::table(
            'piggy_bank_events', function (Blueprint $table) {
            $table->renameColumn('piggybank_id', 'piggy_bank_id');
        }
        );

        Schema::table(
            'piggy_bank_repetitions', function (Blueprint $table) {
            $table->renameColumn('piggybank_id', 'piggy_bank_id');
        }
        );

        // add soft delete to piggy banks
        Schema::table(
            'piggy_banks', function (Blueprint $table) {
            $table->softDeletes();
        }
        );
    }

}
