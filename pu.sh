#!/bin/bash

# set testing environment
cp .env.testing .env

# test!
if [ -z "$1" ]
then
    phpunit --verbose
fi

if [ ! -z "$1" ]
then
    phpunit --verbose tests/helpers/$1.php
fi

# restore .env file
cp .env.local .env
