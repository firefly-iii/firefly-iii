<?php
declare(strict_types = 1);



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class CreateRemindersTable
 *
 */
class CreateRemindersTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('reminders');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'reminders', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('user_id')->unsigned();
            $table->date('startdate');
            $table->date('enddate')->nullable();
            $table->boolean('active');
            $table->boolean('notnow')->default(0);
            $table->integer('remindersable_id')->unsigned()->nullable();
            $table->string('remindersable_type')->nullable();

            // connect reminders to users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        }
        );
    }

}
