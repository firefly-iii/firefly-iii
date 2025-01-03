<?php

/**
 * 2018_01_01_000002_create_oauth_access_tokens_table.php
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
 * Class CreateOauthAccessTokensTable.
 *
 * @codeCoverageIgnore
 */
class CreateOauthAccessTokensTable extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_access_tokens');
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings("PHPMD.ShortMethodName")
     */
    public function up(): void
    {
        if (!Schema::hasTable('oauth_access_tokens')) {
            try {
                Schema::create(
                    'oauth_access_tokens',
                    static function (Blueprint $table): void {
                        $table->string('id', 100)->primary();
                        $table->integer('user_id')->index()->nullable();
                        $table->integer('client_id');
                        $table->string('name')->nullable();
                        $table->text('scopes')->nullable();
                        $table->boolean('revoked');
                        $table->timestamps();
                        $table->dateTime('expires_at')->nullable();
                    }
                );
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "oauth_access_tokens": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }
    }
}
