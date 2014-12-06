<?php
// This is global bootstrap for autoloading
$db   = realpath(__DIR__ . '/_data') . '/testing.sqlite';

if (!file_exists($db)) {
    exec('touch ' . $db);
    exec('php artisan migrate --seed --env=testing');
}
