<?php

/**
 * 2017_08_20_062014_changes_for_v470.php
 * Copyright (c) 2019 james@firefly-iii.org.
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
 * Class ChangesForV470.
 *
 * @codeCoverageIgnore
 */
class ChangesForV470 extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_links');
        Schema::dropIfExists('link_types');
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings("PHPMD.ShortMethodName")
     */
    public function up(): void
    {
        if (!Schema::hasTable('link_types')) {
            try {
                Schema::create(
                    'link_types',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->string('name');
                        $table->string('outward');
                        $table->string('inward');
                        $table->boolean('editable');

                        $table->unique(['name', 'outward', 'inward']);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "link_types": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }

        if (!Schema::hasTable('journal_links')) {
            try {
                Schema::create(
                    'journal_links',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->timestamps();
                        $table->integer('link_type_id', false, true);
                        $table->integer('source_id', false, true);
                        $table->integer('destination_id', false, true);
                        $table->text('comment')->nullable();

                        $table->foreign('link_type_id')->references('id')->on('link_types')->onDelete('cascade');
                        $table->foreign('source_id')->references('id')->on('transaction_journals')->onDelete('cascade');
                        $table->foreign('destination_id')->references('id')->on('transaction_journals')->onDelete('cascade');

                        $table->unique(['link_type_id', 'source_id', 'destination_id']);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "journal_links": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }
    }
}
