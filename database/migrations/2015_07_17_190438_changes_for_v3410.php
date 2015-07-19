<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV3410
 */
class ChangesForV3410 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('attachable_id')->unsigned();
            $table->string('attachable_type');
            $table->integer('user_id')->unsigned();
            $table->string('md5', 32);
            $table->text('filename');
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->text('mime');
            $table->integer('size')->unsigned();
            $table->tinyInteger('uploaded', false, true)->default(0);

        }
        );
    }



    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('attachments');

    }
}
