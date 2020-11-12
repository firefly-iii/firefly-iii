<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class ChangesForV550
 */
class ChangesForV550 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('jobs');
        Schema::create(
            'jobs',
            static function (Blueprint $table) {
                // straight from Laravel (this is the OLD table)
                $table->bigIncrements('id');
                $table->string('queue');
                $table->longText('payload');
                $table->tinyInteger('attempts')->unsigned();
                $table->tinyInteger('reserved')->unsigned();
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
                $table->index(['queue', 'reserved', 'reserved_at']);
            }
        );
        Schema::table(
            'budget_transaction_journal', function (Blueprint $table) {
            $table->dropForeign('budget_id_foreign');
            $table->dropColumn('budget_limit_id');
        }
        );
        Schema::dropIfExists('failed_jobs');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::drop('jobs');
        // this is the NEW table
        Schema::create(
            'jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        }
        );

        Schema::create(
            'failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        }
        );

        Schema::table(
            'budget_transaction_journal', function (Blueprint $table) {
            $table->integer('budget_limit_id', false, true)->nullable()->default(null)->after('budget_id');
            $table->foreign('budget_limit_id','budget_id_foreign')->references('id')->on('budget_limits')->onDelete('set null');


        }
        );
    }
}
