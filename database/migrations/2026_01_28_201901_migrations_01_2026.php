<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes #11620
 */
return new class extends Migration
{

    public function up(): void
    {
        Schema::table(
            'transactions',
            static function (Blueprint $blueprint): void {
                $blueprint->index(['transaction_journal_id','amount'],'idx_tx_journal_amount');
            }
        );

        Schema::table(
            'tag_transaction_journal',
            static function (Blueprint $blueprint): void {
                $blueprint->index(['transaction_journal_id','tag_id'],'idx_ttj_journal_tag');
            }
        );
        Schema::table(
            'transaction_journals',
            static function (Blueprint $blueprint): void {
                $blueprint->index(['deleted_at'],'idx_tj_deleted');
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
