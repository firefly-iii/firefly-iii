#!/bin/bash

DATABASE=./storage/database/database.sqlite
DATABASECOPY=./storage/database/databasecopy.sqlite
ORIGINALENV=./.env
BACKUPENV=./.env.current
TESTINGENV=./.env.testing

# backup current config (if it exists):
if [ -f $ORIGINALENV ]; then
    mv $ORIGINALENV $BACKUPENV
fi

# enable testing config
cp $TESTINGENV $ORIGINALENV

# clear cache:
php artisan cache:clear

if [[ "$@" == "--reset" ]]
then
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
if [[ "$@" == "--notest" ]]
then
    echo "Must not run PHPUnit"
else
    phpunit
fi

# restore current config:
if [ -f $BACKUPENV ]; then
    mv $BACKUPENV $ORIGINALENV
fi