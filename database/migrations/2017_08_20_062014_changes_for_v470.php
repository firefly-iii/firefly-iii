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
        Schema::dropIfExists('link_types');
        Schema::dropIfExists('journal_types');
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
            }
            );
        }

        if (!Schema::hasTable('journal_links')) {
            Schema::create(
                'journal_links', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('link_type_id', false, true);
                $table->integer('source_id', false, true);
                $table->integer('destination_id', false, true);
                $table->text('comment');
                $table->integer('sequence', false, true);
            }
            );
        }
    }
}
