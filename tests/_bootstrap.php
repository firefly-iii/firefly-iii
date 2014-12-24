<?php
$db = realpath(__DIR__ . '/_data') . '/db.sqlite';
if(!file_exists($db)) {
    exec('touch '.$db);
    exec('php artisan migrate --seed --env=testing');
    exec('sqlite3 tests/_data/db.sqlite .dump > tests/_data/dump.sql');
}

/**
 * Class resetToClean
 */
class resetToClean
{
    /**
     *
     */
    static public function clean()
    {
        //exec('cp ' . realpath(__DIR__ . '/_data') . '/clean.sqlite ' . realpath(__DIR__ . '/_data') . '/testing.sqlite');
    }
}