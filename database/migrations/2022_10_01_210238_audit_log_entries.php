<?php

/*
 * 2022_10_01_210238_audit_log_entries.php
 * Copyright (c) 2022 james@firefly-iii.org
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

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @SuppressWarnings("PHPMD.ShortMethodName")
     */
    public function up(): void
    {
        if (!Schema::hasTable('audit_log_entries')) {
            try {
                Schema::create('audit_log_entries', static function (Blueprint $table): void {
                    $table->id();
                    $table->timestamps();
                    $table->softDeletes();

                    $table->integer('auditable_id', false, true);
                    $table->string('auditable_type');

                    $table->integer('changer_id', false, true);
                    $table->string('changer_type');

                    $table->string('action', 255);
                    $table->text('before')->nullable();
                    $table->text('after')->nullable();
                });
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "audit_log_entries": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log_entries');
    }
};
