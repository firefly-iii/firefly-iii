#!/bin/bash

# make sure we own the volumes:
chown -R www-data:www-data $FIREFLY_PATH/storage/export $FIREFLY_PATH/storage/upload
chmod -R 775 $FIREFLY_PATH/storage/export $FIREFLY_PATH/storage/upload

cat .env.docker | envsubst > .env

# if the database is an sqlite database, we need to remove all non-connection variables
if [ "$FF_DB_CONNECTION" == "sqlite" ]; then
    sed -i '/DB_/{/DB_CONNECTION/!d;}' .env
fi

cat .env
composer dump-autoload
php artisan optimize
php artisan package:discover
php artisan firefly:instructions install
exec apache2-foreground
