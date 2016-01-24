#!/bin/bash

# set testing environment
cp .env.testing .env

# set default phpunit.
cp phpunit.default.xml phpunit.xml

# "create" default attachment:
touch storage/upload/at-1.data
touch storage/upload/at-2.data


# delete test databases:
if [ -f storage/database/testing.db ]
then
    rm storage/database/testing.db
fi

if [ -f storage/database/testing-copy.db ]
then
    rm storage/database/testing-copy.db
fi

# test!
if [ -z "$1" ]
then
    echo "Running all tests..."
    phpunit
    result=$?
fi

# test selective..
dirs=("acceptance/Controllers" "acceptance/Controllers/Auth" "acceptance/Controllers/Chart" "unit")
#
if [ ! -z "$1" ]
then
    for i in "${dirs[@]}"
    do
        firstFile="./tests/$i/$1.php"
        secondFile="./tests/$i/$1Test.php"
        if [ -f "$firstFile" ]
        then
            # run it!
            echo "Now running $firstFile"
            phpunit --verbose $firstFile
            result=$?
        fi
        if [ -f "$secondFile" ]
        then
            # run it!
            echo "Now running $secondFile"
            phpunit --verbose $secondFile
            result=$?
        fi


    done
fi


# restore .env file
cp .env.local .env

exit ${result}