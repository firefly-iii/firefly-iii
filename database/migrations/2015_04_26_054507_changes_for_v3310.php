<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class ChangesForV3310 extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('tag_transaction_journal');
        Schema::drop('tags');

    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table(
            'transaction_groups', function (Blueprint $table) {

            // drop column "relation"
            $table->dropColumn('relation');
        }
        );

        /*
         * New table!
         */
        Schema::create(
            'tags', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('user_id')->unsigned();
            $table->string('tag', 1024);
            $table->string('tagMode', 1024);
            $table->date('date')->nullable();
            $table->text('description')->nullable();
            $table->decimal('latitude', 18, 12)->nullable();
            $table->decimal('longitude', 18, 12)->nullable();
            $table->smallInteger('zoomLevel', false, true)->nullable();

            // connect reminders to users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        }
        );


        Schema::create('tag_transaction_journal',function (Blueprint $table) {
            $table->increments('id');
            $table->integer('tag_id')->unsigned();
            $table->integer('transaction_journal_id')->unsigned();

            // link to foreign tables.
            $table->foreign('tag_id', 'tag_grp_id')->references('id')->on('tags')->onDelete('cascade');
            $table->foreign('transaction_journal_id', 'tag_trj_id')->references('id')->on('transaction_journals')->onDelete('cascade');

            // add unique.
            $table->unique(['tag_id', 'transaction_journal_id'], 'tag_t_joined');

        });
    }

}
