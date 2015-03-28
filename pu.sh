#!/bin/bash

# create DB if not exists

if [ ! -f tests/database/db.sqlite ]; then
    touch tests/database/db.sqlite
    php artisan migrate --seed
fi

phpunit