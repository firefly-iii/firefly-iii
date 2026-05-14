<?php

/*
 * Adds the `country_id` column to the `user_groups` table.
 *
 * When set, the universal national-rates adapter pulls exchange rates
 * for that administration from the country's registered provider.
 * NULL = the administration opts out (and the per-user preference
 * `national_rates_country` is used as a fallback).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_groups', static function (Blueprint $table): void {
            $table->unsignedBigInteger('country_id')->nullable()->after('title');
            $table->foreign('country_id')
                ->references('id')
                ->on('countries')
                ->onDelete('set null');
            $table->index('country_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_groups', static function (Blueprint $table): void {
            // SQLite-safe drop sequence.
            try {
                $table->dropForeign(['country_id']);
            } catch (\Throwable $e) {
                // ignore on drivers without named FKs
            }
            try {
                $table->dropIndex(['country_id']);
            } catch (\Throwable $e) {
                // ignore
            }
            $table->dropColumn('country_id');
        });
    }
};
