<?php
declare(strict_types = 1);



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class CreateAccountsTable
 *
 */
class CreateAccountsTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('accounts');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('user_id')->unsigned();
            $table->integer('account_type_id')->unsigned();
            $table->string('name', 100);
            $table->boolean('active');

            // connect accounts to users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // connect accounts to account_types
            $table->foreign('account_type_id')->references('id')->on('account_types')->onDelete('cascade');

            // for a user, the account name must be unique.
            $table->unique(['user_id', 'account_type_id', 'name']);
        }
        );
    }

}
