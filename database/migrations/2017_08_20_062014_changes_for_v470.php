<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class ChangesForV470
 */
class ChangesForV470 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('journal_links');
        Schema::dropIfExists('link_types');

    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('link_types')) {
            Schema::create(
                'link_types', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->string('name');
                $table->string('outward');
                $table->string('inward');
                $table->boolean('editable');

                $table->unique(['name']);
                $table->unique(['outward','inward']);
            }
            );
        }

        if (!Schema::hasTable('journal_links')) {
            Schema::create(
                'journal_links', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->integer('link_type_id', false, true);
                $table->integer('source_id', false, true);
                $table->integer('destination_id', false, true);
                $table->text('comment');
                $table->integer('sequence', false, true);

                $table->foreign('link_type_id')->references('id')->on('link_types')->onDelete('cascade');
                $table->foreign('source_id')->references('id')->on('transaction_journals')->onDelete('cascade');
                $table->foreign('destination_id')->references('id')->on('transaction_journals')->onDelete('cascade');

                $table->unique(['link_type_id','source_id','destination_id']);


            }
            );
        }
    }
}
