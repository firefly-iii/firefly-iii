<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // transaction_currency_user
        if (!Schema::hasTable('transaction_currency_user')) {
            try {
                Schema::create('transaction_currency_user', function (Blueprint $table) {
                    $table->id();
                    $table->timestamps();
                    $table->integer('user_id', false, true);
                    $table->integer('transaction_currency_id', false, true);
                    $table->boolean('user_default')->default(false);
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
                    $table->unique(['user_id', 'transaction_currency_id'],'unique_combo');
                });
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "transaction_currency_user": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }

        // transaction_currency_user_group
        if (!Schema::hasTable('transaction_currency_user_group')) {
            try {
                Schema::create('transaction_currency_user_group', function (Blueprint $table) {
                    $table->id();
                    $table->timestamps();
                    $table->bigInteger('user_group_id', false, true);
                    $table->integer('transaction_currency_id', false, true);
                    $table->boolean('group_default')->default(false);
                    $table->foreign('user_group_id')->references('id')->on('user_groups')->onDelete('cascade');
                    $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
                    $table->unique(['user_group_id', 'transaction_currency_id'],'unique_combo');
                });
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "transaction_currency_user_group": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_currency_user');
        Schema::dropIfExists('transaction_currency_user_group');
    }
};
