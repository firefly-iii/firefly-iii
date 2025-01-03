<?php


/*
 * 2024_05_12_060551_create_account_balance_table.php
 * Copyright (c) 2025 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('account_balances')) {
            Schema::create('account_balances', function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
                $table->string('title', 100)->nullable();
                $table->integer('account_id', false, true);
                $table->integer('transaction_currency_id', false, true);
                $table->date('date')->nullable();
                $table->integer('transaction_journal_id', false, true)->nullable();
                $table->decimal('balance', 32, 12);
                $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
                $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');
                $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');

                $table->unique(['account_id', 'transaction_currency_id', 'transaction_journal_id', 'date', 'title'], 'unique_account_currency');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_balances');
    }
};
