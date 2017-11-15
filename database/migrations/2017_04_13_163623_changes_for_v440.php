<?php

/**
 * 2017_04_13_163623_changes_for_v440.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV440
 */
class ChangesForV440 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('currency_exchange_rates')) {
            Schema::drop('currency_exchange_rates');
        }
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('currency_exchange_rates')) {
            Schema::create(
                'currency_exchange_rates',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->softDeletes();
                    $table->integer('user_id', false, true);
                    $table->integer('from_currency_id', false, true);
                    $table->integer('to_currency_id', false, true);
                    $table->date('date');
                    $table->decimal('rate', 22, 12);
                    $table->decimal('user_rate', 22, 12)->nullable();

                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    $table->foreign('from_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
                    $table->foreign('to_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
                }
            );
        }
        //
        Schema::table(
            'transactions',
            function (Blueprint $table) {
                $table->integer('transaction_currency_id', false, true)->after('description')->nullable();
                $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('set null');
            }
        );
    }
}
