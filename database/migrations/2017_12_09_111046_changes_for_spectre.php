<?php

declare(strict_types=1);

/*
 * 2017_12_09_111046_changes_for_spectre.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class ChangesForSpectre
 */
class ChangesForSpectre extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down()
    {
    }

    /**
     * Run the migrations.
     */
    public function up()
    {
        // create provider table:
        if (!Schema::hasTable('spectre_providers')) {
            Schema::create(
                'spectre_providers',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->softDeletes();
                    //'spectre_id', 'code', 'mode', 'name', 'status', 'interactive', 'automatic_fetch', 'country_code', 'data'
                    $table->integer('spectre_id', false, true);
                    $table->string('code', 100);
                    $table->string('mode', 20);
                    $table->string('status', 20);
                    $table->boolean('interactive')->default(0);
                    $table->boolean('automatic_fetch')->default(0);
                    $table->string('country_code', 3);
                    $table->text('data');
                }
            );
        }
    }
}
