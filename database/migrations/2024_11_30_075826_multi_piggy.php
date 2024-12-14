<?php

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
        // make account_id nullable and the relation also nullable.
        Schema::table('piggy_banks', static function (Blueprint $table): void {
            // 1. drop index
            $table->dropForeign('piggy_banks_account_id_foreign');
        });
        Schema::table('piggy_banks', static function (Blueprint $table): void {
            // 2. make column nullable.
            $table->unsignedInteger('account_id')->nullable()->change();
        });
        Schema::table('piggy_banks', static function (Blueprint $table): void {
            // 3. add currency
            $table->integer('transaction_currency_id', false, true)->after('account_id')->nullable();
            $table->foreign('transaction_currency_id', 'unique_currency')->references('id')->on('transaction_currencies')->onDelete('cascade');
        });
        Schema::table('piggy_banks', static function (Blueprint $table): void {
            // 4. rename columns
            $table->renameColumn('targetamount', 'target_amount');
            $table->renameColumn('startdate', 'start_date');
            $table->renameColumn('targetdate', 'target_date');
            $table->renameColumn('startdate_tz', 'start_date_tz');
            $table->renameColumn('targetdate_tz', 'target_date_tz');
        });
        Schema::table('piggy_banks', static function (Blueprint $table): void {
            // 5. add new index
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
        });

        // rename some fields in piggy bank reps.
        Schema::table('piggy_bank_repetitions', static function (Blueprint $table): void {
            // 6. rename columns
            $table->renameColumn('currentamount', 'current_amount');
            $table->renameColumn('startdate', 'start_date');
            $table->renameColumn('targetdate', 'target_date');
            $table->renameColumn('startdate_tz', 'start_date_tz');
            $table->renameColumn('targetdate_tz', 'target_date_tz');
        });

        // create table account_piggy_bank
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
            $table->dropForeign('unique_currency');
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
};
