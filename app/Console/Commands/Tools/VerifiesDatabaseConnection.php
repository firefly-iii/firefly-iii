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
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class VerifiesDatabaseConnection extends Command
{
    use ShowsFriendlyMessages;

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
        $loops     = 30;
        $loop      = 0;
        $queries   = ['pgsql'  => 'SELECT * FROM pg_catalog.pg_tables;', 'sqlite' => 'SELECT name FROM sqlite_schema;', 'mysql'  => 'SHOW TABLES;'];
        $default   = config('database.default');
        if (!array_key_exists($default, $queries)) {
            $this->friendlyWarning(sprintf('Cannot validate database connection for "%s"', $default));

            return Command::SUCCESS;
        }
        $query     = $queries[$default];
        $connected = false;
        Log::debug(sprintf('Connecting to database "%s"...', config('database.default')));
        while (!$connected && $loop < $loops) {
            try {
                DB::select($query);
                $connected = true;
            } catch (QueryException $e) {
                Log::error(sprintf('Loop #%d: connection failed: %s', $loop, $e->getMessage()));
                $this->friendlyWarning(sprintf('Database connection attempt #%d failed. Sleep for 10 seconds...', $loop + 1));
                sleep(10);
            } catch (Exception $e) {
                Log::error(sprintf('Loop #%d: not connected yet because of a %s: %s', $loop, get_class($e), $e->getMessage()));
                $this->friendlyWarning(sprintf('Database connection attempt #%d failed. Sleep for 10 seconds...', $loop + 1));
                sleep(10);
            }
            ++$loop;
        }
        if ($connected) {
            Log::debug(sprintf('Connected to database after %d attempt(s).', $loop));
            $this->friendlyPositive('Connected to the database.');

            return Command::SUCCESS;
        }
        Log::error('Failed to connect to database.');
        $this->friendlyError('Failed to connect to the database. Is it up?');

        return Command::FAILURE;
    }
}
