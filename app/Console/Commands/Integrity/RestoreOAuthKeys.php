<?php
/**
 * RestoreOAuthKeys.php
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

namespace FireflyIII\Console\Commands\Integrity;

use Artisan;
use Crypt;
use Illuminate\Console\Command;

/**
 * Class RestoreOAuthKeys
 */
class RestoreOAuthKeys extends Command
{
    private const PRIVATE_KEY = 'oauth_private_key';
    private const PUBLIC_KEY  = 'oauth_public_key';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will restore the OAuth keys generated for the system.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:restore-oauth-keys';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->restoreOAuthKeys();

        return 0;
    }

    /**
     *
     */
    private function generateKeys(): void
    {
        Artisan::call('passport:keys');
    }

    /**
     * @return bool
     */
    private function keysInDatabase(): bool
    {
        return app('fireflyconfig')->has(self::PRIVATE_KEY) && app('fireflyconfig')->has(self::PUBLIC_KEY);
    }

    /**
     * @return bool
     */
    private function keysOnDrive(): bool
    {
        $private = storage_path('oauth-private.key');
        $public  = storage_path('oauth-public.key');

        return file_exists($private) && file_exists($public);
    }

    /**
     *
     */
    private function restoreKeysFromDB(): void
    {
        $privateContent = Crypt::decrypt(app('fireflyconfig')->get(self::PRIVATE_KEY)->data);
        $publicContent  = Crypt::decrypt(app('fireflyconfig')->get(self::PUBLIC_KEY)->data);
        $private        = storage_path('oauth-private.key');
        $public         = storage_path('oauth-public.key');
        file_put_contents($private, $privateContent);
        file_put_contents($public, $publicContent);
    }

    /**
     *
     */
    private function restoreOAuthKeys(): void
    {
        if (!$this->keysInDatabase() && !$this->keysOnDrive()) {
            $this->generateKeys();
            $this->storeKeysInDB();
            $this->line('Generated and stored new keys.');

            return;
        }
        if ($this->keysInDatabase() && !$this->keysOnDrive()) {
            $this->restoreKeysFromDB();
            $this->line('Restored OAuth keys from database.');

            return;
        }
        if (!$this->keysInDatabase() && $this->keysOnDrive()) {
            $this->storeKeysInDB();
            $this->line('Stored OAuth keys in database.');

            return;
        }
        $this->line('OAuth keys are OK');
    }

    /**
     *
     */
    private function storeKeysInDB(): void
    {
        $private = storage_path('oauth-private.key');
        $public  = storage_path('oauth-public.key');
        app('fireflyconfig')->set(self::PRIVATE_KEY, Crypt::encrypt(file_get_contents($private)));
        app('fireflyconfig')->set(self::PUBLIC_KEY, Crypt::encrypt(file_get_contents($public)));
    }
}