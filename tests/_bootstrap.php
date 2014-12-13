<?php
// This is global bootstrap for autoloading
$db = realpath(__DIR__ . '/_data') . '/testing.sqlite';

if (!file_exists($db)) {
    exec('touch ' . $db);
    exec('php artisan migrate --seed --env=testing');
}
exec('cp ' . $db . ' ' . realpath(__DIR__ . '/_data') . '/clean.sqlite');

/**
 * Class resetToClean
 * @SuppressWarnings("CamelCase")
 */
class resetToClean
{
    /**
     *
     */
    static public function clean()
    {
        exec('cp ' . realpath(__DIR__ . '/_data') . '/clean.sqlite ' . realpath(__DIR__ . '/_data') . '/testing.sqlite');
    }
}