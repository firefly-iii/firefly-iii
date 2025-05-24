<?php

/**
 * 2016_12_22_150431_changes_for_v430.php
 * Copyright (c) 2019 james@firefly-iii.org.
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
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
/**
 * Class ChangesForV430.
 *
 * @codeCoverageIgnore
 */
class ChangesForV430 extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('available_budgets');
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings("PHPMD.ShortMethodName")
     */
    public function up(): void
    {
        if (!Schema::hasTable('available_budgets')) {
            try {
                Schema::create(
                    'available_budgets',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->integer('user_id', false, true);
                        $table->integer('transaction_currency_id', false, true);
                        $table->decimal('amount', 32, 12);
                        $table->date('start_date');
                        $table->date('end_date');

                        $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "available_budgets": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }
    }
}
