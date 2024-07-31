<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table): void {
                    if (!Schema::hasColumn('transactions', 'balance_before')) {
                        $table->decimal('balance_before', 32, 12)->nullable()->after('amount');
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }

        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table): void {
                    if (!Schema::hasColumn('transactions', 'balance_after')) {
                        $table->decimal('balance_after', 32, 12)->nullable()->after('balance_before');
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }

        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table): void {
                    if (!Schema::hasColumn('transactions', 'balance_dirty')) {
                        $table->boolean('balance_dirty')->default(true)->after('balance_after');
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table): void {
                    if (Schema::hasColumn('transactions', 'balance_before')) {
                        $table->dropColumn('balance_before');
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }

        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table): void {
                    if (Schema::hasColumn('transactions', 'balance_after')) {
                        $table->dropColumn('balance_after');
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }

        try {
            Schema::table(
                'transactions',
                static function (Blueprint $table): void {
                    if (Schema::hasColumn('transactions', 'balance_dirty')) {
                        $table->dropColumn('balance_dirty');
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }
    }
};
