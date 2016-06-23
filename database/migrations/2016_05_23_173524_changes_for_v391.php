<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV391
 */
class ChangesForV391 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('import_jobs');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // new table "import_jobs"
        Schema::create(
            'import_jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('user_id')->unsigned();
            $table->string('key', 12)->unique();
            $table->string('file_type', 12);
            $table->string('status', 45);

            // connect rule groups to users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

        }
        );
    }
}
