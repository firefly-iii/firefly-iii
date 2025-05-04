<?php

/**
 * OAuthKeys.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Support\System;

use FireflyIII\Exceptions\FireflyException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Laravel\Passport\Console\KeysCommand;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class OAuthKeys
 */
class OAuthKeys
{
    private const string PRIVATE_KEY = 'oauth_private_key';
    private const string PUBLIC_KEY  = 'oauth_public_key';

    public static function verifyKeysRoutine(): void
    {
        if (!self::keysInDatabase() && !self::hasKeyFiles()) {
            self::generateKeys();
            self::storeKeysInDB();

            return;
        }
        if (self::keysInDatabase() && !self::hasKeyFiles()) {
            self::restoreKeysFromDB();

            return;
        }
        if (!self::keysInDatabase() && self::hasKeyFiles()) {
            self::storeKeysInDB();
        }
    }

    public static function keysInDatabase(): bool
    {
        $privateKey = '';
        $publicKey  = '';
        // better check if keys are in the database:
        if (app('fireflyconfig')->has(self::PRIVATE_KEY) && app('fireflyconfig')->has(self::PUBLIC_KEY)) {
            try {
                $privateKey = (string) app('fireflyconfig')->get(self::PRIVATE_KEY)?->data;
                $publicKey  = (string) app('fireflyconfig')->get(self::PUBLIC_KEY)?->data;
            } catch (ContainerExceptionInterface|FireflyException|NotFoundExceptionInterface $e) {
                app('log')->error(sprintf('Could not validate keysInDatabase(): %s', $e->getMessage()));
                app('log')->error($e->getTraceAsString());
            }
        }
        if ('' !== $privateKey && '' !== $publicKey) {
            return true;
        }

        return false;
    }

    public static function hasKeyFiles(): bool
    {
        $private = storage_path('oauth-private.key');
        $public  = storage_path('oauth-public.key');

        return file_exists($private) && file_exists($public);
    }

    public static function generateKeys(): void
    {
        \Artisan::registerCommand(new KeysCommand());
        \Artisan::call('firefly-iii:laravel-passport-keys');
    }

    public static function storeKeysInDB(): void
    {
        $private = storage_path('oauth-private.key');
        $public  = storage_path('oauth-public.key');
        app('fireflyconfig')->set(self::PRIVATE_KEY, Crypt::encrypt(\Safe\file_get_contents($private)));
        app('fireflyconfig')->set(self::PUBLIC_KEY, Crypt::encrypt(\Safe\file_get_contents($public)));
    }

    /**
     * @throws FireflyException
     */
    public static function restoreKeysFromDB(): bool
    {
        $privateKey = (string) app('fireflyconfig')->get(self::PRIVATE_KEY)?->data;
        $publicKey  = (string) app('fireflyconfig')->get(self::PUBLIC_KEY)?->data;

        try {
            $privateContent = Crypt::decrypt($privateKey);
            $publicContent  = Crypt::decrypt($publicKey);
        } catch (DecryptException $e) {
            app('log')->error('Could not decrypt pub/private keypair.');
            app('log')->error($e->getMessage());

            // delete config vars from DB:
            app('fireflyconfig')->delete(self::PRIVATE_KEY);
            app('fireflyconfig')->delete(self::PUBLIC_KEY);

            return false;
        }
        $private    = storage_path('oauth-private.key');
        $public     = storage_path('oauth-public.key');
        \Safe\file_put_contents($private, $privateContent);
        \Safe\file_put_contents($public, $publicContent);

        return true;
    }
}
