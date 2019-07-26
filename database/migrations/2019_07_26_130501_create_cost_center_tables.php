<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCostCenterTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $this->createCostCentersTables();
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cost_center_tables');
        Schema::dropIfExists('cost_center_transaction');
        Schema::dropIfExists('cost_center_transaction_journal');        
    }

    private function createCostCentersTables(): void
    {
        if (!Schema::hasTable('cost_centers')) {
            Schema::create(
                'cost_centers',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->softDeletes();
                    $table->integer('user_id', false, true);
                    $table->string('name', 1024);
                    $table->boolean('encrypted')->default(0);

                    // link user id to users table
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                }
            );
        }

        if (!Schema::hasTable('cost_center_transaction')) {
            Schema::create(
                'cost_center_transaction',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('cost_center_id', false, true);
                    $table->integer('transaction_id', false, true);

                    $table->foreign('cost_center_id')->references('id')->on('cost_centers')->onDelete('cascade');
                    $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
                }
            );
        }

        if (!Schema::hasTable('cost_center_transaction_journal')) {
            Schema::create(
                'cost_center_transaction_journal',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('cost_center_id', false, true);
                    $table->integer('transaction_journal_id', false, true);
                    $table->foreign('cost_center_id')->references('id')->on('cost_centers')->onDelete('cascade');
                    $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');
                }
            );
        }
    }
}
