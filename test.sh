#!/bin/bash

DATABASE=./storage/database/database.sqlite
DATABASECOPY=./storage/database/databasecopy.sqlite
ORIGINALENV=./.env
BACKUPENV=./.env.current
TESTINGENV=./.env.testing

# do something with flags:
resetTestFlag=''
testflag=''
coverageflag=''
acceptancetestclass=''
verbalflag=''
testsuite=''

while getopts 'vcrta:s:' flag; do
  case "${flag}" in
    r)
        resetTestFlag='true'
    ;;
    t)
        testflag='true'
    ;;
    c)
        coverageflag='true'
    ;;
    v)
        verbalflag=' -v --debug'
        echo "Will be verbal about it"
    ;;
    a)
        acceptancetestclass=./tests/acceptance/$OPTARG
        echo "Will only run acceptance test $OPTARG"
    ;;
    s)
        testsuite="--testsuite $OPTARG"
        echo "Will only run test suite '$OPTARG'"
    ;;
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
if [[ $resetTestFlag == "true" ]]
then
    echo "Must reset database"

    # touch files to make sure they exist.
    touch $DATABASE
    touch $DATABASECOPY

    # truncate original database file
    truncate $DATABASE --size 0

    # run migration
    php artisan migrate:refresh --seed

    # call test data generation script
    $(which php) /sites/FF3/test-data/artisan generate:data testing sqlite
    # copy new database over backup (resets backup)
    cp $DATABASE $DATABASECOPY
fi

# do not reset database (optional)
if [[ $resetTestFlag == "" ]]
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
        echo "Must run PHPUnit without coverage:"
        echo "phpunit --stop-on-error $verbalflag $acceptancetestclass $testsuite"
        phpunit --stop-on-error $verbalflag $acceptancetestclass $testsuite
    else
        echo "Must run PHPUnit with coverage"
        echo "phpunit --stop-on-error $verbalflag --configuration phpunit.coverage.xml $acceptancetestclass $testsuite"
        phpunit --stop-on-error $verbalflag --configuration phpunit.coverage.xml $acceptancetestclass $testsuite
    fi
fi

# restore current config:
if [ -f $BACKUPENV ]; then
    mv $BACKUPENV $ORIGINALENV
fi