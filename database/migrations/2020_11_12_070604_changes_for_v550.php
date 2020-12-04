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
        // recreate jobs table.
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

        // expand budget / transaction journal table.
        Schema::table(
            'budget_transaction_journal', function (Blueprint $table) {
            $table->dropForeign('budget_id_foreign');
            $table->dropColumn('budget_limit_id');
        }
        );

        // drop failed jobs table.
        Schema::dropIfExists('failed_jobs');

        // drop fields from budget limits
        Schema::table(
            'budget_limits',
            static function (Blueprint $table) {
                $table->dropColumn('period');
                $table->dropColumn('generated');
            }
        );
        Schema::dropIfExists('webhook_messages');
        Schema::dropIfExists('webhooks');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // drop and recreate jobs table.
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

        // create new failed_jobs table.
        Schema::create(
            'failed_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        }
        );

        // update budget / transaction journal table.
        Schema::table(
            'budget_transaction_journal', function (Blueprint $table) {
            $table->integer('budget_limit_id', false, true)->nullable()->default(null)->after('budget_id');
            $table->foreign('budget_limit_id', 'budget_id_foreign')->references('id')->on('budget_limits')->onDelete('set null');
        }
        );

        // append budget limits table.
        // i swear I dropped & recreated this field 15 times already.
        Schema::table(
            'budget_limits',
            static function (Blueprint $table) {
                $table->string('period', 12)->nullable();
                $table->boolean('generated')->default(false);
            }
        );

        // new webhooks table
        Schema::create(
            'webhooks',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->integer('user_id', false, true);
                $table->string('title', 512)->index();
                $table->string('secret', 32)->index();
                $table->boolean('active')->default(true);
                $table->unsignedSmallInteger('trigger', false);
                $table->unsignedSmallInteger('response', false);
                $table->unsignedSmallInteger('delivery', false);
                $table->string('url', 1024);
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['user_id', 'title']);
            }
        );

        // new webhook_messages table
        Schema::create(
            'webhook_messages',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->boolean('sent')->default(false);
                $table->boolean('errored')->default(false);

                $table->integer('webhook_id', false, true);
                $table->string('uuid', 64);
                $table->longText('message');

                $table->foreign('webhook_id')->references('id')->on('webhooks')->onDelete('cascade');
            }
        );

        Schema::create(
            'webhook_attempts',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->integer('webhook_message_id', false, true);
                $table->unsignedSmallInteger('status_code')->default(0);

                $table->longText('logs')->nullable();
                $table->longText('response')->nullable();

                $table->foreign('webhook_message_id')->references('id')->on('webhook_messages')->onDelete('cascade');
            }
        );
    }
}
