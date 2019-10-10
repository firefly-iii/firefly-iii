<?php

/**
 * 2018_06_08_200526_changes_for_v475.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 *
 * Class ChangesForV475
 */
class ChangesForV475 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('recurrences_repetitions');
        Schema::dropIfExists('recurrences_meta');
        Schema::dropIfExists('rt_meta');
        Schema::dropIfExists('recurrences_transactions');
        Schema::dropIfExists('recurrences');
    }

    /**
     * Run the migrations.
     * @SuppressWarnings(PHPMD.ShortMethodName)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create(
            'recurrences', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('user_id', false, true);
            $table->integer('transaction_type_id', false, true);

            $table->string('title', 1024);
            $table->text('description');

            $table->date('first_date');
            $table->date('repeat_until')->nullable();
            $table->date('latest_date')->nullable();
            $table->smallInteger('repetitions', false, true);

            $table->boolean('apply_rules')->default(true);
            $table->boolean('active')->default(true);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('transaction_type_id')->references('id')->on('transaction_types')->onDelete('cascade');
        }
        );

        Schema::create(
            'recurrences_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('recurrence_id', false, true);
            $table->integer('transaction_currency_id', false, true);
            $table->integer('foreign_currency_id', false, true)->nullable();
            $table->integer('source_id', false, true);
            $table->integer('destination_id', false, true);

            $table->decimal('amount', 22, 12);
            $table->decimal('foreign_amount', 22, 12)->nullable();
            $table->string('description', 1024);


            $table->foreign('recurrence_id')->references('id')->on('recurrences')->onDelete('cascade');
            $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
            $table->foreign('foreign_currency_id')->references('id')->on('transaction_currencies')->onDelete('set null');
            $table->foreign('source_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('destination_id')->references('id')->on('accounts')->onDelete('cascade');
        }
        );


        Schema::create(
            'recurrences_repetitions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('recurrence_id', false, true);
            $table->string('repetition_type', 50);
            $table->string('repetition_moment', 50);
            $table->smallInteger('repetition_skip', false, true);
            $table->smallInteger('weekend', false, true);

            $table->foreign('recurrence_id')->references('id')->on('recurrences')->onDelete('cascade');
        }
        );

        Schema::create(
            'recurrences_meta', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('recurrence_id', false, true);

            $table->string('name', 50);
            $table->text('value');

            $table->foreign('recurrence_id')->references('id')->on('recurrences')->onDelete('cascade');
        }
        );

        Schema::create(
            'rt_meta', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('rt_id', false, true);

            $table->string('name', 50);
            $table->text('value');

            $table->foreign('rt_id')->references('id')->on('recurrences_transactions')->onDelete('cascade');
        }
        );


    }
}
