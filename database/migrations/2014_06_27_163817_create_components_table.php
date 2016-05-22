<?php
declare(strict_types = 1);



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class CreateComponentsTable
 *
 */
class CreateComponentsTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('components');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'components', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('name', 50);
            $table->integer('user_id')->unsigned();
            $table->string('class', 20);

            // connect components to users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // for a user, the component type & name must be unique.
            $table->unique(['user_id', 'class', 'name']);
        }
        );

    }

}
