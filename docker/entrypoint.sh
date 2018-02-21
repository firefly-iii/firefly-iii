#!/bin/bash

# make sure we own the volumes:
chown -R www-data:www-data $FIREFLY_PATH/storage/export $FIREFLY_PATH/storage/upload
chmod -R 775 $FIREFLY_PATH/storage/export $FIREFLY_PATH/storage/upload

cat .env.docker | envsubst > .env

# if the database is an sqlite database, we need to remove all non-connection variables
if [ "$FF_DB_CONNECTION" == "sqlite" ]; then
    sed -i '/DB_/{/DB_CONNECTION/!d;}' .env

    if [ ! -e "$FIREFLY_PATH/storage/database/database.sqlite" ]; then 
        # check if the database file exists, if not, create one
        touch $FIREFLY_PATH/storage/database/database.sqlite
    fi

    # make sure own the sqlite database file
    chown -R www-data:www-data $FIREFLY_PATH/storage/database
    chmod -R 775 $FIREFLY_PATH/storage/database
fi

cat .env
composer dump-autoload
php artisan optimize
php artisan package:discover
php artisan firefly:instructions install
exec apache2-foreground
