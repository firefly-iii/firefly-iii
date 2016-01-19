#!/bin/bash

# set testing environment
cp .env.testing .env

# set cover:
cp phpunit.cover.xml phpunit.xml

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
phpdbg -qrr /usr/local/bin/phpunit

# restore .env file
cp .env.local .env

# restore cover
cp phpunit.default.xml phpunit.xml