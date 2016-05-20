<?php
declare(strict_types = 1);



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
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
            $table->string('email', 100)->unique();
            $table->string('password', 60);
            $table->rememberToken();
            $table->string('reset', 32)->nullable();
        }
        );
    }

}
