#!/bin/bash

searchFile=""
deleteDatabases=false

while getopts ":nhf:" opt; do
  case $opt in
    n)
      # echo "-n was triggered: new database!" >&2
      deleteDatabases=true
      ;;
    f)
      #echo "-f was triggered: file! $OPTARG" >&2
      searchFile=$OPTARG
      ;;
    h)
      echo "n: new database" >&2
      echo "f: which file to run" >&2
      ;;
    :)
      echo "Option -$OPTARG requires an argument." >&2
      exit 1
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      exit 1
      ;;
  esac
done

# set testing environment
cp .env.testing .env

# set default phpunit.
cp phpunit.default.xml phpunit.xml

# "create" default attachment:
touch storage/upload/at-1.data
touch storage/upload/at-2.data

# delete databses:
if [ "$deleteDatabases" = true ] ; then
    echo "Will delete and recreate the databases."

    # delete test database:
    if [ -f storage/database/testing.db ]
    then
        echo "Deleted testing.db"
        rm storage/database/testing.db
    fi

    # delete test database copy:
    if [ -f storage/database/testing-copy.db ]
    then
        echo "Delete testing-copy.db"
        rm storage/database/testing-copy.db
    fi
fi

# do not delete database:
if [ "$deleteDatabases" = false ] ; then
    echo "Will not delete databases."
fi

# test!
if [ "$searchFile" ==  "" ]
then
    echo "Running all tests..."
    phpunit
    result=$?
fi

# test selective..
dirs=("acceptance/Controllers" "acceptance/Controllers/Auth" "acceptance/Controllers/Chart" "unit")
#
if [ "$searchFile" != "" ]
then
    echo "Will run test for '$searchFile'"
    for i in "${dirs[@]}"
    do
        firstFile="./tests/$i/$searchFile.php"
        secondFile="./tests/$i/"$searchFile"Test.php"
        if [ -f "$firstFile" ]
        then
            # run it!
            echo "Found file  '$firstFile'"
            phpunit --verbose $firstFile
            result=$?
        fi
        if [ -f "$secondFile" ]
        then
            # run it!
            echo "Found file  '$secondFile'"
            phpunit --verbose $secondFile
            result=$?
        fi
    done
fi

# restore .env file
cp .env.local .env

exit ${result}