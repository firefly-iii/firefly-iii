#!/bin/bash

DATABASE=./storage/database/database.sqlite
DATABASECOPY=./storage/database/databasecopy.sqlite
ORIGINALENV=./.env
BACKUPENV=./.env.current
TESTINGENV=./.env.testing

# do something with flags:
rflag=''
tflag=''

while getopts 'rt' flag; do
  case "${flag}" in
    r) rflag='true' ;;
    t) tflag='true' ;;
    *) error "Unexpected option ${flag}" ;;
  esac
done



# backup current config (if it exists):
if [ -f $ORIGINALENV ]; then
    mv $ORIGINALENV $BACKUPENV
fi

# enable testing config
cp $TESTINGENV $ORIGINALENV

# clear cache:
php artisan cache:clear

# reset database (optional)
if [[ $rflag == "true" ]]
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

# do not reset database (optional)
if [[ $rflag == "" ]]
then
    echo "Will not reset database"
fi

# take database from copy:
cp $DATABASECOPY $DATABASE

# run PHPUnit
if [[ $tflag == "" ]]
then
    echo "Must not run PHPUnit"
else
    echo "Must run PHPUnit"
    phpunit
fi

# restore current config:
if [ -f $BACKUPENV ]; then
    mv $BACKUPENV $ORIGINALENV
fi