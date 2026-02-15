<?php
/*
 * VerifiesDatabaseConnectionTrait.php
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

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait VerifiesDatabaseConnectionTrait
{

    protected function verifyDatabaseConnection(): bool
    {
        $loops   = 30;
        $loop    = 0;
        $queries = [
            'pgsql'  => 'SELECT * FROM pg_catalog.pg_tables;',
            'sqlite' => 'SELECT name FROM sqlite_schema;',
            'mysql'  => 'SHOW TABLES;',
        ];
        $default = config('database.default');
        if (!array_key_exists($default, $queries)) {
            $this->friendlyWarning(sprintf('Cannot validate database connection for "%s"', $default));
            return true;
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
        return $connected;
    }

}
