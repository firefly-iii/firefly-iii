<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class ChangesForSpectre
 */
class ChangesForSpectre extends Migration
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
        // create provider table:
        if (!Schema::hasTable('spectre_providers')) {
            Schema::create(
                'spectre_providers',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->softDeletes();
                    //'spectre_id', 'code', 'mode', 'name', 'status', 'interactive', 'automatic_fetch', 'country_code', 'data'
                    $table->integer('spectre_id', false, true);
                    $table->string('code', 100);
                    $table->string('mode', 20);
                    $table->string('status', 20);
                    $table->boolean('interactive')->default(0);
                    $table->boolean('automatic_fetch')->default(0);
                    $table->string('country_code', 3);
                    $table->text('data');
                }
            );
        }
    }
}
