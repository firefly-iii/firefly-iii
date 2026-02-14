<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes #11620
 */
return new class extends Migration {
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }

    public function up(): void
    {
        try {
            Schema::table('transactions', static function (Blueprint $blueprint): void {
                $blueprint->index(['transaction_journal_id', 'amount'], 'idx_tx_journal_amount');
            });
        } catch (QueryException $e) {
            $message = $e->getMessage();

            // ignore duplicate key name as error.
            if (str_contains($message, ' Duplicate key name')) {
                return;
            }
            Log::error(sprintf('Error when creating index: %s', $e->getMessage()));
        }
        try {
            Schema::table('tag_transaction_journal', static function (Blueprint $blueprint): void {
                $blueprint->index(['transaction_journal_id', 'tag_id'], 'idx_ttj_journal_tag');
            });
        } catch (QueryException $e) {
            $message = $e->getMessage();

            // ignore duplicate key name as error.
            if (str_contains($message, ' Duplicate key name')) {
                return;
            }
            Log::error(sprintf('Error when creating index: %s', $e->getMessage()));
        }
        try {
            Schema::table('transaction_journals', static function (Blueprint $blueprint): void {
                $blueprint->index(['deleted_at'], 'idx_tj_deleted');
            });
        } catch (QueryException $e) {
            $message = $e->getMessage();

            // ignore duplicate key name as error.
            if (str_contains($message, ' Duplicate key name')) {
                return;
            }
            Log::error(sprintf('Error when creating index: %s', $e->getMessage()));
        }
    }
};
