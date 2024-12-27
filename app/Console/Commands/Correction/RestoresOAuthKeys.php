<?php

/*
 * RestoresOAuthKeys.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Support\System\OAuthKeys;
use Illuminate\Console\Command;

class RestoresOAuthKeys extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Will restore the OAuth keys generated for the system.';
    protected $signature   = 'correction:restore-oauth-keys';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->restoreOAuthKeys();

        return 0;
    }

    private function restoreOAuthKeys(): void
    {
        if (!$this->keysInDatabase() && !$this->keysOnDrive()) {
            $this->generateKeys();
            $this->storeKeysInDB();
            $this->friendlyInfo('Generated and stored new keys.');

            return;
        }
        if ($this->keysInDatabase() && !$this->keysOnDrive()) {
            $result = $this->restoreKeysFromDB();
            if (true === $result) {
                $this->friendlyInfo('Restored OAuth keys from database.');

                return;
            }
            $this->generateKeys();
            $this->storeKeysInDB();
            $this->friendlyInfo('Generated and stored new keys.');

            return;
        }
        if (!$this->keysInDatabase() && $this->keysOnDrive()) {
            $this->storeKeysInDB();
            $this->friendlyInfo('Stored OAuth keys in database.');

            return;
        }
    }

    private function keysInDatabase(): bool
    {
        return OAuthKeys::keysInDatabase();
    }

    private function keysOnDrive(): bool
    {
        return OAuthKeys::hasKeyFiles();
    }

    private function generateKeys(): void
    {
        OAuthKeys::generateKeys();
    }

    private function storeKeysInDB(): void
    {
        OAuthKeys::storeKeysInDB();
    }

    private function restoreKeysFromDB(): bool
    {
        return OAuthKeys::restoreKeysFromDB();
    }
}
