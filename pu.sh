#!/bin/bash

# create DB if not exists
rm -f tests/database/db.sqlite
touch tests/database/db.sqlite
php artisan migrate --seed
phpunit