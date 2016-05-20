<?php
declare(strict_types = 1);



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class CreatePreferencesTable
 *
 */
class CreatePreferencesTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('preferences');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'preferences', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('user_id')->unsigned();
            $table->string('name');
            $table->text('data');

            // connect preferences to users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // only one preference per name per user
            $table->unique(['user_id', 'name']);
        }
        );
    }

}
