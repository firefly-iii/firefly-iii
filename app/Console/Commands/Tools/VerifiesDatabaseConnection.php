<?php

declare(strict_types=1);

/*
 * VerifiesDatabaseConnection.php
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

namespace FireflyIII\Console\Commands\Tools;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use Illuminate\Console\Command;

class VerifiesDatabaseConnection extends Command
{
    use ShowsFriendlyMessages;
    use VerifiesDatabaseConnectionTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'firefly-iii:verify-database-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command tries to connect to the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connected = $this->verifyDatabaseConnection();
        if ($connected) {
            $this->friendlyPositive('Connected to the database.');

            return Command::SUCCESS;
        }
        $this->friendlyError('Failed to connect to the database. Is it up?');

        return Command::FAILURE;
    }
}
