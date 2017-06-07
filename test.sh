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

featureflag=''
featuretestclass=''

unitflag=''
unittestclass=''

verbalflag=''
testsuite=''
configfile='phpunit.xml';

while getopts 'vcrtf:u:s:' flag; do
  case "${flag}" in
    r)
        resetTestFlag='true'
    ;;
    t)
        testflag='true'
    ;;
    c)
        coverageflag='true'
        configfile='phpunit.coverage.xml';
    ;;
    v)
        verbalflag=' -v --debug'
        echo "Will be verbal about it"
    ;;
    f)
        featureflag='true'
        featuretestclass=./tests/Feature/$OPTARG
        echo "Will only run Feature test $OPTARG"
    ;;
    u)
        unitflag='true'
        unittestclass=./tests/Unit/$OPTARG
        echo "Will only run Unit test $OPTARG"
    ;;
    s)
        testsuite="--testsuite $OPTARG"
        echo "Will only run test suite '$OPTARG'"
    ;;
    *) error "Unexpected option ${flag}" ;;
  esac
done

if [[ $coverageflag == "true" && ($featureflag == "true" || $unitflag == "true") ]]
then
    echo "Use config file specific.xml"
    configfile='phpunit.coverage.specific.xml'
fi


# backup current config (if it exists):
if [ -f $ORIGINALENV ]; then
    mv $ORIGINALENV $BACKUPENV
fi

# enable testing config
cp $TESTINGENV $ORIGINALENV

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
    $(which php) /sites/FF3/test-data/artisan generate:data local sqlite

    # also run upgrade routine:
    $(which php) /sites/FF3/firefly-iii/artisan firefly:upgrade-database

    # copy new database over backup (resets backup)
    cp $DATABASE $DATABASECOPY
fi

# do not reset database (optional)
if [[ $resetTestFlag == "" ]]
then
    echo "Will not reset database"
fi

echo "Copy test database over original"
# take database from copy:
cp $DATABASECOPY $DATABASE

echo "clear caches and what-not.."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan twig:clean
php artisan view:clear

# run PHPUnit
if [[ $testflag == "" ]]
then
    echo "Must not run PHPUnit"
else
    echo "Must run PHPUnit"

    if [[ $coverageflag == "" ]]
    then
        echo "Must run PHPUnit without coverage:"

        echo "phpunit $verbalflag --configuration $configfile $featuretestclass $unittestclass $testsuite"
        phpunit $verbalflag  --configuration $configfile $featuretestclass $unittestclass $testsuite
    else
        echo "Must run PHPUnit with coverage"
        echo "phpunit $verbalflag --configuration $configfile $featuretestclass $unittestclass $testsuite"
        phpunit $verbalflag --configuration $configfile $featuretestclass $unittestclass $testsuite
    fi
fi

# restore current config:
if [ -f $BACKUPENV ]; then
    mv $BACKUPENV $ORIGINALENV
fi