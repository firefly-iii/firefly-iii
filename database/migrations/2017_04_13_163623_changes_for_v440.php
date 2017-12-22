<?php
/**
 * 2017_04_13_163623_changes_for_v440.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
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

        Schema::table(
            'transactions',
            function (Blueprint $table) {
                $table->integer('transaction_currency_id', false, true)->after('description')->nullable();
                $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('set null');
            }
        );
    }
}
