<?php
//exec('php artisan migrate --seed --env=testing');
// This is global bootstrap for autoloading
//
//
//$db = realpath(__DIR__ . '/_data') . '/testing.sqlite';
//if (!file_exists($db)) {
//    echo 'Recreating database...' . "\n";
//    exec('touch ' . $db);
//
//} else {
//    echo 'Database exists!' . "\n";
//}
//echo 'Copy database to clean database (turned off)...' . "\n";
//exec('cp ' . $db . ' ' . realpath(__DIR__ . '/_data') . '/clean.sqlite');
//
//
///**
// * Class resetToClean
// * @SuppressWarnings("CamelCase")
// */
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