<?php


/*
 * 2025_07_10_065736_rename_tag_mode.php
 * Copyright (c) 2025 james@firefly-iii.org
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
