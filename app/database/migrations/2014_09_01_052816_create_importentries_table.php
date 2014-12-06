<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateImportentriesTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('importentries');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'importentries', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->string('class', 200);
                $table->integer('importmap_id')->unsigned();
                $table->integer('old')->unsigned();
                $table->integer('new')->unsigned();

                // connect import map.
                $table->foreign('importmap_id')->references('id')->on('importmaps')->onDelete('cascade');
            }
        );
    }

}
