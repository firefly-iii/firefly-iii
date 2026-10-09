<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table('categories', static function (Blueprint $table): void {
                if (!Schema::hasColumn('categories', 'color')) {
                    $table->string('color', 6)->nullable()->after('name');
                }
            });
        } catch (RuntimeException $e) {
            Log::error(sprintf('Could not add column: %s', $e->getMessage()));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('categories', static function (Blueprint $table): void {
                if (Schema::hasColumn('categories', 'color')) {
                    $table->dropColumn('color');
                }
            });
        } catch (RuntimeException $e) {
            Log::error(sprintf('Could not drop column: %s', $e->getMessage()));
        }
    }
};
