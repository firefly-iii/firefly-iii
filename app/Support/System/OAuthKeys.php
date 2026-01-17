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
use FireflyIII\Support\Facades\FireflyConfig;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Console\KeysCommand;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Safe\Exceptions\FilesystemException;

use function Safe\file_get_contents;
use function Safe\file_put_contents;

/**
 * Class OAuthKeys
 */
class OAuthKeys
{
    private const string PRIVATE_KEY = 'oauth_private_key';
    private const string PUBLIC_KEY  = 'oauth_public_key';

    public static function generateKeys(): void
    {
        Log::debug('Will now run generateKeys()');
        Artisan::registerCommand(new KeysCommand());
        Artisan::call('firefly-iii:laravel-passport-keys');
        Log::debug('Done with generateKeys()');
    }

    public static function hasKeyFiles(): bool
    {
        Log::debug('hasKeyFiles()');
        $private       = storage_path('oauth-private.key');
        $public        = storage_path('oauth-public.key');
        $privateExists = file_exists($private);
        $publicExists  = file_exists($public);

        Log::debug(sprintf('Private key file at "%s" exists? %s', $private, var_export($privateExists, true)));
        Log::debug(sprintf('Public key file at "%s" exists ? %s', $public, var_export($publicExists, true)));

        $result        = file_exists($private) && file_exists($public);
        Log::debug(sprintf('Method will return %s', var_export($result, true)));

        return $result;
    }

    public static function keysInDatabase(): bool
    {
        $privateKey = '';
        $publicKey  = '';
        // better check if keys are in the database:
        $hasPrivate = FireflyConfig::has(self::PRIVATE_KEY);
        $hasPublic  = FireflyConfig::has(self::PUBLIC_KEY);

        Log::debug(sprintf('keysInDatabase: hasPrivate:%s, hasPublic:%s', var_export($hasPrivate, true), var_export($hasPublic, true)));

        if ($hasPrivate && $hasPublic) {
            try {
                $privateKey = trim((string)FireflyConfig::get(self::PRIVATE_KEY)?->data);
                $publicKey  = trim((string)FireflyConfig::get(self::PUBLIC_KEY)?->data);
            } catch (ContainerExceptionInterface|FireflyException|NotFoundExceptionInterface $e) {
                Log::error(sprintf('Could not validate keysInDatabase(): %s', $e->getMessage()));
                Log::error($e->getTraceAsString());
            }
        }
        if ('' === $privateKey) {
            Log::warning('Private key in DB is unexpectedly an empty string.');
        }
        if ('' === $publicKey) {
            Log::warning('Public key in DB is unexpectedly an empty string.');
        }
        if ('' !== $privateKey) {
            Log::debug(sprintf('SHA2 hash of private key in DB: %s', hash('sha256', $privateKey)));
        }
        if ('' !== $publicKey) {
            Log::debug(sprintf('SHA2 hash of public key in DB : %s', hash('sha256', $publicKey)));
        }
        $return     = '' !== $privateKey && '' !== $publicKey;
        Log::debug(sprintf('keysInDatabase will return %s', var_export($return, true)));

        return $return;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws FireflyException
     * @throws NotFoundExceptionInterface
     * @throws FilesystemException
     */
    public static function restoreKeysFromDB(): bool
    {
        Log::debug('restoreKeysFromDB()');
        $privateKey = (string)FireflyConfig::get(self::PRIVATE_KEY)?->data;
        $publicKey  = (string)FireflyConfig::get(self::PUBLIC_KEY)?->data;

        if ('' === $privateKey) {
            Log::warning('Private key is not in the database.');
        }
        if ('' === $publicKey) {
            Log::warning('Public key is not in the database.');
        }

        try {
            $privateContent = trim(Crypt::decrypt($privateKey));
            $publicContent  = trim(Crypt::decrypt($publicKey));
        } catch (DecryptException $e) {
            Log::error('Could not decrypt pub/private keypair.');
            Log::error($e->getMessage());

            // delete config vars from DB:
            FireflyConfig::delete(self::PRIVATE_KEY);
            FireflyConfig::delete(self::PUBLIC_KEY);
            Log::debug('Done with generateKeysFromDB(), return FALSE');

            return false;
        }
        $private    = storage_path('oauth-private.key');
        $public     = storage_path('oauth-public.key');
        file_put_contents($private, $privateContent);
        file_put_contents($public, $publicContent);

        Log::debug(sprintf('Will store private key with hash "%s" in file "%s"', hash('sha256', $privateContent), $private));
        Log::debug(sprintf('Will store public key with hash "%s" in file "%s"', hash('sha256', $publicContent), $public));
        Log::debug('Done with generateKeysFromDB()');

        return true;
    }

    public static function storeKeysInDB(): void
    {
        $private        = storage_path('oauth-private.key');
        $public         = storage_path('oauth-public.key');
        $privateContent = file_get_contents($private);
        $publicContent  = file_get_contents($public);
        FireflyConfig::set(self::PRIVATE_KEY, Crypt::encrypt($privateContent));
        FireflyConfig::set(self::PUBLIC_KEY, Crypt::encrypt($publicContent));

        Log::debug(sprintf('Will store the content of file "%s" as "%s" in the database (hash: %s)', $private, self::PRIVATE_KEY, hash('sha256', $privateContent)));
        Log::debug(sprintf('Will store the content of file "%s" as "%s" in the database (hash: %s)', $public, self::PUBLIC_KEY, hash('sha256', $publicContent)));
    }

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
}
