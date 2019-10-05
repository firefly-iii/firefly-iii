<?php

/**
 * 2019_01_28_193833_changes_for_v4710.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangesForV4710 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('group_journals');
        Schema::dropIfExists('transaction_groups');
    }

    /**
     * Run the migrations.
     * @SuppressWarnings(PHPMD.ShortMethodName)
     *
     * @return void
     */
    public function up(): void
    {
        if (!Schema::hasTable('transaction_groups')) {
            Schema::create(
                'transaction_groups', function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->integer('user_id', false, true);
                $table->string('title', 1024)->nullable();


                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
            );
        }

        if (!Schema::hasTable('group_journals')) {
            Schema::create(
                'group_journals',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('transaction_group_id', false, true);
                    $table->integer('transaction_journal_id', false, true);

                    $table->foreign('transaction_group_id')->references('id')->on('transaction_groups')->onDelete('cascade');
                    $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');

                    // unique combi:
                    $table->unique(['transaction_group_id', 'transaction_journal_id'], 'unique_in_group');
                }
            );
        }

    }
}
