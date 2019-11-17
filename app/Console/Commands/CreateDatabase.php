<?php

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
            $this->info('This command currently applies to MySQL connections only.');
        }
        // try to set up a raw connection:
        $dsn     = sprintf('mysql:host=%s;charset=utf8mb4', env('DB_HOST'));
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
        $stmt = $pdo->prepare('SHOW DATABASES WHERE `Database` = ?');
        $stmt->execute([env('DB_DATABASE')]);
        $result = $stmt->fetch();
        if (false === $result) {
            $this->error(sprintf('Database "%s" does not exist.', env('DB_DATABASE')));

            // try to create it.
            $stmt = $pdo->query(sprintf('CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;', env('DB_DATABASE')));
            $stmt->execute();
            $stmt->fetch();
            $this->info(sprintf('Created database "%s"', env('DB_DATABASE')));

            return 0;
        }
        $this->info(sprintf('Database "%s" exists.', env('DB_DATABASE')));

        return 0;
    }
}
