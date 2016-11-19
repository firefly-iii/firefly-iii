#!/bin/bash

DATABASE=./storage/database/database.sqlite
DATABASECOPY=./storage/database/databasecopy.sqlite
ORIGINALENV=./.env
BACKUPENV=./.env.current
TESTINGENV=./.env.testing

# do something with flags:
resetestflag=''
testflag=''
coverageflag=''

while getopts 'crt' flag; do
  case "${flag}" in
    r) resetestflag='true' ;;
    t) testflag='true' ;;
    c) coverageflag='true' ;;
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
if [[ $resetestflag == "true" ]]
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
if [[ $resetestflag == "" ]]
then
    echo "Will not reset database"
fi

# take database from copy:
cp $DATABASECOPY $DATABASE

# run PHPUnit
if [[ $testflag == "" ]]
then
    echo "Must not run PHPUnit"
else
    echo "Must run PHPUnit"

    if [[ $coverageflag == "" ]]
    then
        echo "Must run PHPUnit without coverage"
        phpunit
    else
        echo "Must run PHPUnit with coverage"
        phpunit --configuration phpunit.coverage.xml
    fi
fi

# restore current config:
if [ -f $BACKUPENV ]; then
    mv $BACKUPENV $ORIGINALENV
fi