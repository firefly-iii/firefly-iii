<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateSupportTables
 */
class CreateSupportTables extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::drop('account_types');
        Schema::drop('transaction_currencies');
        Schema::drop('transaction_types');
        Schema::drop('jobs');
        Schema::drop('password_resets');
        Schema::drop('permissions');
        Schema::drop('roles');
        Schema::drop('permission_role');
        Schema::drop('sessions');

    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*
         * account_types
         */
        if (!Schema::hasTable('account_types')) {
            Schema::create(
                'account_types', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->string('type', 50);

                // type must be unique.
                $table->unique(['type']);
            }
            );
        }
        /*
         * transaction_currencies
         */
        if (!Schema::hasTable('transaction_currencies')) {
            Schema::create(
                'transaction_currencies', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->string('code', 3);
                $table->string('name', 255);
                $table->string('symbol', 12);

                // code must be unique.
                $table->unique(['code']);

            });
        }

        /*
         * transaction_types
         */
        if (!Schema::hasTable('transaction_types')) {
            Schema::create(
                'transaction_types', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->string('type', 50);

                // type must be unique.
                $table->unique(['type']);

            });
        }

        /*
         * jobs
         */
        if (!Schema::hasTable('jobs')) {
            Schema::create(
                'jobs', function (Blueprint $table) {

                // straight from Laravel
                $table->bigIncrements('id');
                $table->string('queue');
                $table->longText('payload');
                $table->tinyInteger('attempts')->unsigned();
                $table->tinyInteger('reserved')->unsigned();
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
                $table->index(['queue', 'reserved', 'reserved_at']);

            });
        }

        /*
         * password_resets
         */
        if (!Schema::hasTable('password_resets')) {
            Schema::create(
                'password_resets', function (Blueprint $table) {
                // straight from laravel
                $table->string('email')->index();
                $table->string('token')->index();
                $table->timestamp('created_at');

            });
        }

        /*
         * permissions
         */
        if (!Schema::hasTable('permissions')) {
            Schema::create(
                'permissions', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->string('name')->unique();
                $table->string('display_name')->nullable();
                $table->string('description')->nullable();
            });
        }

        /*
         * roles
         */
        if (!Schema::hasTable('roles')) {
            Schema::create(
                'roles', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->string('name')->unique();
                $table->string('display_name')->nullable();
                $table->string('description')->nullable();
            });
        }

        /*
         * permission_role
         */
        if (!Schema::hasTable('permission_role')) {
            Schema::create(
                'permission_role', function (Blueprint $table) {
                $table->integer('permission_id')->unsigned();
                $table->integer('role_id')->unsigned();
                $table->foreign('permission_id')->references('id')->on('permissions')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('role_id')->references('id')->on('roles')->onUpdate('cascade')->onDelete('cascade');

                $table->primary(['permission_id', 'role_id']);
            });
        }

        /*
         * sessions
         */
        if (!Schema::hasTable('sessions')) {
            Schema::create(
                'sessions', function (Blueprint $table) {
                $table->string('id')->unique();
                $table->integer('user_id')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('payload');
                $table->integer('last_activity');
            });
        }

    }
}
