<?php
declare(strict_types = 1);


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class CreateAccountMetaTable
 *
 */
class CreateAccountMetaTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('account_meta');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create(
            'account_meta', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('account_id')->unsigned();
            $table->string('name');
            $table->text('data');

            $table->unique(['account_id', 'name']);

            // link to account!
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');


        }
        );
    }

}
