<?php


/*
 * CallsLaravelPassportKeys.php
 * Copyright (c) 2025 james@firefly-iii.org.
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

namespace FireflyIII\Console\Commands\System;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CallsLaravelPassportKeys extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Calls the Laravel "passport:keys" but doesn\'t exit 1.';
    protected $signature   = 'firefly-iii:laravel-passport-keys';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Artisan::call('passport:keys --no-interaction', []);
        $result = Artisan::output();
        if (str_contains($result, 'Encryption keys already exist')) {
            $this->friendlyInfo('Encryption keys exist already.');

            return CommandAlias::SUCCESS;
        }
        $this->friendlyPositive('Encryption keys have been created, nice!');

        return CommandAlias::SUCCESS;
    }
}
