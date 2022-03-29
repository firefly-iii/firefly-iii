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

use Artisan;
use Crypt;
use FireflyIII\Exceptions\FireflyException;
use Illuminate\Contracts\Encryption\DecryptException;
use Laravel\Passport\Console\KeysCommand;
use Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class OAuthKeys
 */
class OAuthKeys
{
    private const PRIVATE_KEY = 'oauth_private_key';
    private const PUBLIC_KEY  = 'oauth_public_key';

    /**
     *
     */
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

    /**
     * @return bool
     */
    public static function keysInDatabase(): bool
    {
        $privateKey = '';
        $publicKey  = '';
        // better check if keys are in the database:
        if (app('fireflyconfig')->has(self::PRIVATE_KEY) && app('fireflyconfig')->has(self::PUBLIC_KEY)) {
            try {
                $privateKey = (string) app('fireflyconfig')->get(self::PRIVATE_KEY)?->data;
                $publicKey  = (string) app('fireflyconfig')->get(self::PUBLIC_KEY)?->data;
            } catch (ContainerExceptionInterface|NotFoundExceptionInterface|FireflyException $e) {
                Log::error(sprintf('Could not validate keysInDatabase(): %s', $e->getMessage()));
                Log::error($e->getTraceAsString());
            }
        }
        if ('' !== $privateKey && '' !== $publicKey) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public static function hasKeyFiles(): bool
    {
        $private = storage_path('oauth-private.key');
        $public  = storage_path('oauth-public.key');

        return file_exists($private) && file_exists($public);
    }

    /**
     *
     */
    public static function generateKeys(): void
    {
        Artisan::registerCommand(new KeysCommand());
        Artisan::call('passport:keys');
    }

    /**
     *
     */
    public static function storeKeysInDB(): void
    {
        $private = storage_path('oauth-private.key');
        $public  = storage_path('oauth-public.key');
        app('fireflyconfig')->set(self::PRIVATE_KEY, Crypt::encrypt(file_get_contents($private)));
        app('fireflyconfig')->set(self::PUBLIC_KEY, Crypt::encrypt(file_get_contents($public)));
    }

    /**
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws FireflyException
     * @throws NotFoundExceptionInterface
     */
    public static function restoreKeysFromDB(): bool
    {
        $privateKey = (string) app('fireflyconfig')->get(self::PRIVATE_KEY)?->data;
        $publicKey  = (string) app('fireflyconfig')->get(self::PUBLIC_KEY)?->data;
        try {
            $privateContent = Crypt::decrypt($privateKey);
            $publicContent  = Crypt::decrypt($publicKey);
        } catch (DecryptException $e) {
            Log::error('Could not decrypt pub/private keypair.');
            Log::error($e->getMessage());

            // delete config vars from DB:
            app('fireflyconfig')->delete(self::PRIVATE_KEY);
            app('fireflyconfig')->delete(self::PUBLIC_KEY);

            return false;
        }
        $private = storage_path('oauth-private.key');
        $public  = storage_path('oauth-public.key');
        file_put_contents($private, $privateContent);
        file_put_contents($public, $publicContent);
        return true;
    }

}
