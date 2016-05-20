<?php
declare(strict_types = 1);



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class CreateLimitRepeatTable
 *
 */
class CreateLimitRepeatTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('limit_repetitions');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'limit_repetitions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('limit_id')->unsigned();
            $table->date('startdate');
            $table->date('enddate');
            $table->decimal('amount', 10, 2);

            $table->unique(['limit_id', 'startdate', 'enddate']);

            // connect limit
            $table->foreign('limit_id')->references('id')->on('limits')->onDelete('cascade');
        }
        );
    }

}
