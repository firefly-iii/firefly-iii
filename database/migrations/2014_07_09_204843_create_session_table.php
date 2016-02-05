<?php

use Illuminate\Database\Migrations\Migration;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class CreateSessionTable
 *
 */
class CreateSessionTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sessions');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'sessions', function ($table) {
            $table->string('id')->unique();
            $table->integer('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity');
        }
        );
    }

}
