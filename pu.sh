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
phpunit

# restore .env file
cp .env.local .env
