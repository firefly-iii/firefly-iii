<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangesForV4710 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_journals');
        Schema::dropIfExists('transaction_groups');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('transaction_groups')) {
            Schema::create(
                'transaction_groups', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->integer('user_id', false, true);
                $table->string('title', 1024)->nullable();


                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
            );
        }

        if (!Schema::hasTable('group_journals')) {
            Schema::create(
                'group_journals',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('transaction_group_id', false, true);
                    $table->integer('transaction_journal_id', false, true);

                    $table->foreign('transaction_group_id')->references('id')->on('transaction_groups')->onDelete('cascade');
                    $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');

                    // unique combi:
                    $table->unique(['transaction_group_id', 'transaction_journal_id'],'unique_in_group');
                }
            );
        }

    }
}
