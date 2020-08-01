#!/usr/bin/env bash

# enable test .env file.
cp .ci/.env.ci ../.env

# download test database
wget --quiet https://raw.githubusercontent.com/firefly-iii/test-data/main/test_db.sqlite -o storage/database/test_db.sqlite

# run phpunit
./vendor/bin/phpunit
