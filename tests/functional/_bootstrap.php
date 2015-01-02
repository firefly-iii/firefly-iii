<?php
$db = realpath(__DIR__ . '/../_data') . '/db.sqlite';
if (!file_exists($db)) {
    $out = [];
    exec('touch ' . $db);
    exec('php artisan migrate --seed --env=testing', $out);
    exec('sqlite3 tests/_data/db.sqlite .dump > tests/_data/dump.sql', $out);
}
