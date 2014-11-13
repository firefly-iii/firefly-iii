<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateImportmapsTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('importmaps');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'importmaps', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->integer('user_id')->unsigned();
                $table->string('file', 500);
                $table->integer('totaljobs')->unsigned();
                $table->integer('jobsdone')->unsigned();

                // connect maps to users
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
        );
    }

}
