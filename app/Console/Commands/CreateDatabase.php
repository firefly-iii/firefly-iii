<?php


/**
 * CreateDatabase.php
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

namespace FireflyIII\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use PDOException;


/**
 * Class CreateDatabase
 */
class CreateDatabase extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tries to create the database if it doesn\'t exist yet.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:create-database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ('mysql' !== env('DB_CONNECTION')) {
            $this->info(sprintf('CreateDB does not apply to "%s", skipped.', env('DB_CONNECTION')));

            return 0;
        }
        // try to set up a raw connection:
        $dsn     = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', env('DB_HOST', 'localhost'), env('DB_PORT', '3306'));
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, env('DB_USERNAME'), env('DB_PASSWORD'), $options);
        } catch (PDOException $e) {
            $this->error(sprintf('Error when connecting to DB: %s', $e->getMessage()));

            return 1;
        }
        // with PDO, try to list DB's (
        $stmt   = $pdo->query('SHOW DATABASES;');
        $exists = false;
        // slightly more complex but less error prone.
        foreach ($stmt as $row) {
            $name = $row['Database'] ?? false;
            if ($name === env('DB_DATABASE')) {
                $exists = true;
            }
        }
        if (false === $exists) {
            $this->error(sprintf('Database "%s" does not exist.', env('DB_DATABASE')));

            // try to create it.
            $pdo->exec(sprintf('CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;', env('DB_DATABASE')));
            $this->info(sprintf('Created database "%s"', env('DB_DATABASE')));

            return 0;
        }
        $this->info(sprintf('Database "%s" exists.', env('DB_DATABASE')));

        return 0;
    }
}
