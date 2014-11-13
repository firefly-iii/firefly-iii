<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateFailedJobsTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('failed_jobs');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'failed_jobs', function (Blueprint $table) {
                $table->increments('id');
                $table->text('connection');
                $table->text('queue');
                $table->text('payload');
                $table->timestamp('failed_at');
            }
        );
    }

}
