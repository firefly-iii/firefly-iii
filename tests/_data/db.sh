#!/bin/bash
touch tests/_data/db.sqlite
php artisan migrate --seed
sqlite3 tests/_data/db.sqlite .dump > tests/_data/dump.sql
exit 0