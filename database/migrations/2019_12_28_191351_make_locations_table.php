<?php

/**
 * 2019_12_28_191351_make_locations_table.php
 * Copyright (c) 2020 james@firefly-iii.org.
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

/**
 * Class MakeLocationsTable.
 *
 * @codeCoverageIgnore
 */
class MakeLocationsTable extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings("PHPMD.ShortMethodName")
     */
    public function up(): void
    {
        if (!Schema::hasTable('locations')) {
            try {
                Schema::create(
                    'locations',
                    static function (Blueprint $table): void {
                        $table->bigIncrements('id');
                        $table->timestamps();
                        $table->softDeletes();

                        $table->integer('locatable_id', false, true);
                        $table->string('locatable_type', 255);

                        $table->decimal('latitude', 12, 8)->nullable();
                        $table->decimal('longitude', 12, 8)->nullable();
                        $table->smallInteger('zoom_level', false, true)->nullable();
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "locations": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }
    }
}
