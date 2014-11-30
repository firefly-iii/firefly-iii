<?php

use Illuminate\Database\Migrations\Migration;

class CacheTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cache');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'cache', function ($table) {
                $table->string('key')->unique();
                $table->text('value');
                $table->integer('expiration');
            }
        );
    }

}
