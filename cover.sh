#!/bin/bash

# set testing environment
cp .env.testing .env

# set cover:
cp phpunit.cover.xml phpunit.xml

# test!
if [ -z "$1" ]
then
    phpdbg -qrr /usr/local/bin/phpunit
fi

# directories to look in:
#dirs=("controllers" "database" "factories" "generators" "helpers" "models" "middleware" "repositories" "support")
#
#if [ ! -z "$1" ]
#then
#    for i in "${dirs[@]}"
#    do
#        firstFile="./tests/$i/$1.php"
#        secondFile="./tests/$i/$1Test.php"
#        if [ -f "$firstFile" ]
#        then
#            # run it!
#                phpunit --verbose $firstFile
#                exit $?
#        fi
#        if [ -f "$secondFile" ]
#        then
#            # run it!
#                phpunit --verbose $secondFile
#                exit $?
#        fi
#
#
#    done
#
#fi

# restore .env file
cp .env.local .env

# restore cover
cp phpunit.default.xml phpunit.xml