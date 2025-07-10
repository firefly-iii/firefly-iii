<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // normal case
            Schema::table('tags', static function (Blueprint $table): void {
                if (Schema::hasColumn('tags', 'tagMode') && !Schema::hasColumn('piggy_banks', 'tag_mode')) {
                    $table->renameColumn('tagMode', 'tag_mode');
                }
            });
            // lower case just in case (haha)
            Schema::table('tags', static function (Blueprint $table): void {
                if (Schema::hasColumn('tags', 'tagmode') && !Schema::hasColumn('piggy_banks', 'tag_mode')) {
                    $table->renameColumn('tagmode', 'tag_mode');
                }
            });
        } catch (RuntimeException $e) {
            Log::error(sprintf('Could not rename column: %s', $e->getMessage()));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('tags', static function (Blueprint $table): void {
                if (Schema::hasColumn('tags', 'tag_mode') && !Schema::hasColumn('piggy_banks', 'tagMode')) {
                    $table->renameColumn('tag_mode', 'tagMode');
                }
            });
        } catch (RuntimeException $e) {
            Log::error(sprintf('Could not rename column: %s', $e->getMessage()));
        }
    }
};
