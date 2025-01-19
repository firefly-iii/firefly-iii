<?php

/*
 * CreateDatabase.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\System;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use Illuminate\Console\Command;
use PDO;

class CreatesDatabase extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Tries to create the database if it doesn\'t exist yet.';

    protected $signature   = 'firefly-iii:create-database';

    public function handle(): int
    {
        if ('mysql' !== env('DB_CONNECTION')) { // @phpstan-ignore larastan.noEnvCallsOutsideOfConfig */
            $this->friendlyInfo(sprintf('CreateDB does not apply to "%s", skipped.', env('DB_CONNECTION')));

            return 0;
        }
        // try to set up a raw connection:
        $exists  = false;
        $dsn     = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', env('DB_HOST'), env('DB_PORT'));

        if ('' !== (string) env('DB_SOCKET')) {
            $dsn = sprintf('mysql:unix_socket=%s;charset=utf8mb4', env('DB_SOCKET'));
        }
        $this->friendlyLine(sprintf('DSN is %s', $dsn));

        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // when it fails, display error
        try {
            $pdo = new \PDO($dsn, (string) env('DB_USERNAME'), (string) env('DB_PASSWORD'), $options);
        } catch (\PDOException $e) {
            $this->friendlyError(sprintf('Error when connecting to DB: %s', $e->getMessage()));

            return 1;
        }

        // only continue when no error.
        // with PDO, try to list DB's (
        /** @var array $stmt */
        $stmt    = $pdo->query('SHOW DATABASES;');
        // slightly more complex but less error-prone.
        foreach ($stmt as $row) {
            $name = $row['Database'] ?? false;
            if ($name === env('DB_DATABASE')) {
                $exists = true;
            }
        }
        if (false === $exists) {
            $this->friendlyError(sprintf('Database "%s" does not exist.', env('DB_DATABASE')));

            // try to create it.
            $pdo->exec(sprintf('CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;', env('DB_DATABASE')));
            $this->friendlyInfo(sprintf('Created database "%s"', env('DB_DATABASE')));
        }
        if (true === $exists) {
            $this->friendlyInfo(sprintf('Database "%s" exists.', env('DB_DATABASE')));
        }

        return 0;
    }
}
