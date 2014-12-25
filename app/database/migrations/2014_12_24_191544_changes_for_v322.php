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
        // TODO
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
