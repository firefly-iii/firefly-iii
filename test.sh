#!/bin/bash

DATABASE=storage/database/database.sqlite
DATABASECOPY=storage/database/databasecopy.sqlite


# backup current config:
mv .env .env.current

# enable testing config
cp .env.testing .env

# clear cache:
php artisan cache:clear

if [ "$1" == "--reset" ]; then
    echo "Must reset database"

    # touch files to make sure they exist.
    touch $DATABASE
    touch $DATABASECOPY

    # truncate original database file
    truncate $DATABASE --size 0

    # run migration
    php artisan migrate:refresh --seed

    # copy new database over backup (resets backup)
    cp $DATABASE $DATABASECOPY
fi

# take database from copy:
cp $DATABASECOPY $DATABASE

# run PHPUnit

phpunit

# restore current config:
mv .env.current .env