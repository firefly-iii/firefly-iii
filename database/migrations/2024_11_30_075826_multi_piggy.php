<?php


/*
 * 2024_11_30_075826_multi_piggy.php
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // make account_id nullable and the relation also nullable.
        try {
            Schema::table('piggy_banks', static function (Blueprint $table): void {
                if (self::hasForeign('piggy_banks', 'piggy_banks_account_id_foreign')) {
                    $table->dropForeign('piggy_banks_account_id_foreign');
                }
            });
        } catch (RuntimeException $e) {
            Log::error('Could not drop foreign key "piggy_banks_account_id_foreign". Probably not an issue.');
        }
        Schema::table('piggy_banks', static function (Blueprint $table): void {
            // 2. make column nullable.
            $table->unsignedInteger('account_id')->nullable()->change();
        });
        Schema::table('piggy_banks', static function (Blueprint $table): void {
            // 3. add currency
            if (!Schema::hasColumn('piggy_banks', 'transaction_currency_id')) {
                $table->integer('transaction_currency_id', false, true)->after('account_id')->nullable();
            }
            if (!self::hasForeign('piggy_banks', 'unique_currency')) {
                $table->foreign('transaction_currency_id', 'unique_currency')->references('id')->on('transaction_currencies')->onDelete('cascade');
            }
        });
        Schema::table('piggy_banks', static function (Blueprint $table): void {
            // 4. rename columns
            if (Schema::hasColumn('piggy_banks', 'targetamount') && !Schema::hasColumn('piggy_banks', 'target_amount')) {
                $table->renameColumn('targetamount', 'target_amount');
            }
            if (Schema::hasColumn('piggy_banks', 'startdate') && !Schema::hasColumn('piggy_banks', 'start_date')) {
                $table->renameColumn('startdate', 'start_date');
            }
            if (Schema::hasColumn('piggy_banks', 'targetdate') && !Schema::hasColumn('piggy_banks', 'target_date')) {
                $table->renameColumn('targetdate', 'target_date');
            }
            if (Schema::hasColumn('piggy_banks', 'targetdate') && !Schema::hasColumn('startdate_tz', 'start_date_tz')) {
                $table->renameColumn('startdate_tz', 'start_date_tz');
            }
            if (Schema::hasColumn('piggy_banks', 'targetdate_tz') && !Schema::hasColumn('target_date_tz', 'start_date_tz')) {
                $table->renameColumn('targetdate_tz', 'target_date_tz');
            }
        });
        Schema::table('piggy_banks', static function (Blueprint $table): void {
            // 5. add new index
            if (!self::hasForeign('piggy_banks', 'piggy_banks_account_id_foreign')) {
                $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
            }
        });

        // rename some fields in piggy bank reps.
        Schema::table('piggy_bank_repetitions', static function (Blueprint $table): void {
            // 6. rename columns
            if (Schema::hasColumn('piggy_bank_repetitions', 'currentamount') && !Schema::hasColumn('piggy_bank_repetitions', 'current_amount')) {
                $table->renameColumn('currentamount', 'current_amount');
            }
            if (Schema::hasColumn('piggy_bank_repetitions', 'startdate') && !Schema::hasColumn('piggy_bank_repetitions', 'start_date')) {
                $table->renameColumn('startdate', 'start_date');
            }
            if (Schema::hasColumn('piggy_bank_repetitions', 'targetdate') && !Schema::hasColumn('piggy_bank_repetitions', 'target_date')) {
                $table->renameColumn('targetdate', 'target_date');
            }
            if (Schema::hasColumn('piggy_bank_repetitions', 'startdate_tz') && !Schema::hasColumn('piggy_bank_repetitions', 'start_date_tz')) {
                $table->renameColumn('startdate_tz', 'start_date_tz');
            }
            if (Schema::hasColumn('piggy_bank_repetitions', 'targetdate_tz') && !Schema::hasColumn('piggy_bank_repetitions', 'target_date_tz')) {
                $table->renameColumn('targetdate_tz', 'target_date_tz');
            }
        });

        // create table account_piggy_bank
        if (!Schema::hasTable('account_piggy_bank')) {
            Schema::create('account_piggy_bank', static function (Blueprint $table): void {
                $table->id();
                $table->integer('account_id', false, true);
                $table->integer('piggy_bank_id', false, true);
                $table->decimal('current_amount', 32, 12)->default('0');
                $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
                $table->foreign('piggy_bank_id')->references('id')->on('piggy_banks')->onDelete('cascade');
                $table->unique(['account_id', 'piggy_bank_id'], 'unique_piggy_save');
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('piggy_banks', static function (Blueprint $table): void {
            // 1. drop account index again.
            $table->dropForeign('piggy_banks_account_id_foreign');

            // rename columns again.
            $table->renameColumn('target_amount', 'targetamount');
            $table->renameColumn('start_date', 'startdate');
            $table->renameColumn('target_date', 'targetdate');
            $table->renameColumn('start_date_tz', 'startdate_tz');
            $table->renameColumn('target_date_tz', 'targetdate_tz');

            // 3. drop currency again + index
            if (self::hasForeign('piggy_banks', 'unique_currency')) {
                $table->dropForeign('unique_currency');
            }
            $table->dropColumn('transaction_currency_id');

            // 2. make column non-nullable.
            $table->unsignedInteger('account_id')->change();

            // 5. add new index
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        // rename some fields in piggy bank reps.
        Schema::table('piggy_bank_repetitions', static function (Blueprint $table): void {
            // 6. rename columns
            $table->renameColumn('current_amount', 'currentamount');
            $table->renameColumn('start_date', 'startdate');
            $table->renameColumn('target_date', 'targetdate');
            $table->renameColumn('start_date_tz', 'startdate_tz');
            $table->renameColumn('target_date_tz', 'targetdate_tz');
        });

        Schema::dropIfExists('account_piggy_bank');
    }

    protected static function hasForeign(string $table, string $column)
    {

        $foreignKeysDefinitions = Schema::getForeignKeys($table);
        foreach ($foreignKeysDefinitions as $foreignKeyDefinition) {
            if ($foreignKeyDefinition['name'] === $column) {
                return true;
            }
        }

        return false;
    }
};
