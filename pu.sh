#!/bin/bash

# set testing environment
cp .env.testing .env

# set default phpunit.
cp phpunit.default.xml phpunit.xml

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
