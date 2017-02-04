#!/bin/bash
# Checks if there's a composer.json, and if so, installs/runs composer.
# Only runs when we connect the app to sandstorm (so once).
set -euo pipefail



cd /opt/app

cp .env.sandstorm .env

if [ -f /opt/app/composer.json ] ; then
    if [ ! -f composer.phar ] ; then
        curl -sS https://getcomposer.org/installer | php
    fi
    php composer.phar install --no-dev --no-suggest
fi

# link storage folder
rm -rf /opt/app/storage
ln -s /var/storage /opt/app