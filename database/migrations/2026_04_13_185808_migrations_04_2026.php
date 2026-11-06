<?php

/*
 * 2026_04_13_185808_migrations_04_2026.php
 * Copyright (c) 2026 james@firefly-iii.org
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

return new class extends Migration {
    /**
     * Reverse the migrations.
     */
    public function down(): void {}

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // drop old OAuth tables.
        Schema::dropIfExists('oauth_access_tokens');
        Schema::dropIfExists('oauth_auth_codes');
        Schema::dropIfExists('oauth_clients');
        Schema::dropIfExists('oauth_personal_access_clients');
        Schema::dropIfExists('oauth_refresh_tokens');

        // create new stuff
        if (!Schema::hasTable('oauth_auth_codes')) {
            try {
                Schema::create('oauth_auth_codes', function (Blueprint $table) {
                    $table->char('id', 80)->primary();
                    $table->foreignId('user_id')->index();
                    $table->foreignUuid('client_id');
                    $table->text('scopes')->nullable();
                    $table->boolean('revoked');
                    $table->dateTime('expires_at')->nullable();
                });
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "oauth_auth_codes": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }

        if (!Schema::hasTable('oauth_access_tokens')) {
            try {
                Schema::create('oauth_access_tokens', function (Blueprint $table) {
                    $table->char('id', 80)->primary();
                    $table->foreignId('user_id')->nullable()->index();
                    $table->foreignUuid('client_id');
                    $table->string('name')->nullable();
                    $table->text('scopes')->nullable();
                    $table->boolean('revoked');
                    $table->timestamps();
                    $table->dateTime('expires_at')->nullable();
                });
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "oauth_access_tokens": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }
        if (!Schema::hasTable('oauth_refresh_tokens')) {
            try {
                Schema::create('oauth_refresh_tokens', function (Blueprint $table) {
                    $table->char('id', 80)->primary();
                    $table->char('access_token_id', 80)->index();
                    $table->boolean('revoked');
                    $table->dateTime('expires_at')->nullable();
                });
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "oauth_refresh_tokens": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }

        if (!Schema::hasTable('oauth_clients')) {
            try {
                Schema::create('oauth_clients', function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->nullableMorphs('owner');
                    $table->string('name');
                    $table->string('secret')->nullable();
                    $table->string('provider')->nullable();
                    $table->text('redirect_uris');
                    $table->text('grant_types');
                    $table->boolean('revoked');
                    $table->timestamps();
                });
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "oauth_clients": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }

        if (!Schema::hasTable('oauth_device_codes')) {
            try {
                Schema::create('oauth_device_codes', function (Blueprint $table) {
                    $table->char('id', 80)->primary();
                    $table->foreignId('user_id')->nullable()->index();
                    $table->foreignUuid('client_id')->index();
                    $table->char('user_code', 8)->unique();
                    $table->text('scopes');
                    $table->boolean('revoked');
                    $table->dateTime('user_approved_at')->nullable();
                    $table->dateTime('last_polled_at')->nullable();
                    $table->dateTime('expires_at')->nullable();
                });
            } catch (QueryException $e) {
                app('log')->error(sprintf('Could not create table "oauth_device_codes": %s', $e->getMessage()));
                app('log')->error('If this table exists already (see the error message), this is not a problem. Other errors? Please open a discussion on GitHub.');
            }
        }
    }
};
