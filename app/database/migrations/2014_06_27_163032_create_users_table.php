<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateUsersTable
 */
class CreateUsersTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'users', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->string('email', 100);
                $table->string('password', 60);
                $table->string('reset', 32)->nullable();
                $table->string('remember_token', 255)->nullable();
                $table->unique('email');
            }
        );
    }

}
