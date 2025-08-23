<?php


/*
 * 2025_08_19_180459_create_webhook_details_tables.php
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
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TABLE_ALREADY_EXISTS = 'If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.';
    private const TABLE_ERROR          = 'Could not create table "%s": %s';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('webhook_triggers')) {
            Schema::create('webhook_triggers', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->smallInteger('key')->unsigned();
                $table->string('title', 100);
                $table->unique(['key', 'title']);
            });
        }
        if (!Schema::hasTable('webhook_responses')) {
            Schema::create('webhook_responses', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->smallInteger('key')->unsigned();
                $table->string('title', 100);
                $table->unique(['key', 'title']);
            });
        }
        if (!Schema::hasTable('webhook_deliveries')) {
            Schema::create('webhook_deliveries', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->smallInteger('key')->unsigned();
                $table->string('title', 100);
                $table->unique(['key', 'title']);
            });
        }

        // webhook_webhook_trigger
        if (!Schema::hasTable('webhook_webhook_trigger')) {
            try {
                Schema::create(
                    'webhook_webhook_trigger',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->integer('webhook_id', false, true);
                        $table->bigInteger('webhook_trigger_id', false, true);
                        $table->foreign('webhook_id')->references('id')->on('webhooks')->onDelete('cascade');
                        $table->foreign('webhook_trigger_id','link_to_trigger')->references('id')->on('webhook_triggers')->onDelete('cascade');

                        // unique combi:
                        $table->unique(['webhook_id', 'webhook_trigger_id']);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'webhook_webhook_trigger', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }


        // webhook_webhook_response
        if (!Schema::hasTable('webhook_webhook_response')) {
            try {
                Schema::create(
                    'webhook_webhook_response',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->integer('webhook_id', false, true);
                        $table->bigInteger('webhook_response_id', false, true);
                        $table->foreign('webhook_id')->references('id')->on('webhooks')->onDelete('cascade');
                        $table->foreign('webhook_response_id','link_to_response')->references('id')->on('webhook_responses')->onDelete('cascade');

                        // unique combi:
                        $table->unique(['webhook_id', 'webhook_response_id']);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'webhook_webhook_response', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }

        // webhook_webhook_delivery
        if (!Schema::hasTable('webhook_webhook_delivery')) {
            try {
                Schema::create(
                    'webhook_webhook_delivery',
                    static function (Blueprint $table): void {
                        $table->increments('id');
                        $table->integer('webhook_id', false, true);
                        $table->bigInteger('webhook_delivery_id', false, true);
                        $table->foreign('webhook_id')->references('id')->on('webhooks')->onDelete('cascade');
                        $table->foreign('webhook_delivery_id','link_to_delivery')->references('id')->on('webhook_deliveries')->onDelete('cascade');

                        // unique combi:
                        $table->unique(['webhook_id', 'webhook_delivery_id']);
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf(self::TABLE_ERROR, 'webhook_webhook_delivery', $e->getMessage()));
                app('log')->error(self::TABLE_ALREADY_EXISTS);
            }
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_webhook_delivery');
        Schema::dropIfExists('webhook_webhook_trigger');
        Schema::dropIfExists('webhook_webhook_response');

        Schema::dropIfExists('webhook_triggers');
        Schema::dropIfExists('webhook_responses');
        Schema::dropIfExists('webhook_deliveries');
    }
};
