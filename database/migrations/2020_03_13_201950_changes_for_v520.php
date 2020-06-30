<?php


/**
 * 2020_03_13_201950_changes_for_v520.php
 * Copyright (c) 2020 james@firefly-iii.org
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
 * Class ChangesForV520.
 */
class ChangesForV520 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_budgets');
        Schema::dropIfExists('telemetry');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (!Schema::hasTable('auto_budgets')) {
            Schema::create(
                'auto_budgets',
                static function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->softDeletes();
                    $table->integer('budget_id', false, true);
                    $table->integer('transaction_currency_id', false, true);
                    $table->tinyInteger('auto_budget_type', false, true)->default(1);
                    $table->decimal('amount', 22, 12);
                    $table->string('period', 50);

                    $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
                    $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
                }
            );
        }

        if (!Schema::hasTable('telemetry')) {
            Schema::create(
                'telemetry',
                static function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->dateTime('submitted')->nullable();
                    $table->integer('user_id', false, true)->nullable();
                    $table->string('installation_id', 50);
                    $table->string('type', 25);
                    $table->string('key', 50);
                    $table->text('value');

                    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                }
            );
        }
    }
}
