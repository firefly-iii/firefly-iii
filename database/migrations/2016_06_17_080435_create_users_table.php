<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateUsersTable
 */
class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('users')) {
            Schema::create(
                'users', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->string('email', 255);
                $table->string('password', 60);
                $table->string('remember_token', 100);
                $table->string('reset', 32);
                $table->tinyInteger('blocked', false, true)->default('0');
                $table->string('blocked_code', 25)->nullable();
            }
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }
}
