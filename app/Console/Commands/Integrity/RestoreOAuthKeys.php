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
use FireflyIII\Support\System\OAuthKeys;
use Illuminate\Console\Command;

/**
 * Class RestoreOAuthKeys
 */
class RestoreOAuthKeys extends Command
{
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
        OAuthKeys::generateKeys();
    }

    /**
     * @return bool
     */
    private function keysInDatabase(): bool
    {
        return OAuthKeys::keysInDatabase();
    }

    /**
     * @return bool
     */
    private function keysOnDrive(): bool
    {
        return OAuthKeys::hasKeyFiles();
    }

    /**
     *
     */
    private function restoreKeysFromDB(): void
    {
        OAuthKeys::restoreKeysFromDB();
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
        OAuthKeys::storeKeysInDB();
    }
}