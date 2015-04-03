#!/bin/bash

# backup .env file.
cp .env .env.backup

# set testing environment
cp .env.testing .env

# test!
phpunit --verbose

# restore .env file
mv .env.backup .env
