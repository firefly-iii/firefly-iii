#!/bin/bash

# make sure the correct directories exists (suggested by @chrif):
mkdir -p $FIREFLY_PATH/storage/app/public
mkdir -p $FIREFLY_PATH/storage/build
mkdir -p $FIREFLY_PATH/storage/database
mkdir -p $FIREFLY_PATH/storage/debugbar
mkdir -p $FIREFLY_PATH/storage/export
mkdir -p $FIREFLY_PATH/storage/framework/cache/data
mkdir -p $FIREFLY_PATH/storage/framework/sessions
mkdir -p $FIREFLY_PATH/storage/framework/testing
mkdir -p $FIREFLY_PATH/storage/framework/views
mkdir -p $FIREFLY_PATH/storage/logs
mkdir -p $FIREFLY_PATH/storage/upload


if [[ $DB_CONNECTION == "sqlite" ]]
then
    touch $FIREFLY_PATH/storage/database/database.sqlite
fi

# make sure we own the volumes:
chown -R www-data:www-data -R $FIREFLY_PATH
chmod -R 775 $FIREFLY_PATH

# remove any lingering files that may break upgrades:
rm -f $FIREFLY_PATH/storage/logs/laravel.log

cat .env.docker | envsubst > .env
composer dump-autoload
php artisan package:discover

php artisan migrate --seed
php artisan firefly:upgrade-database
php artisan firefly:verify
php artisan passport:install
php artisan cache:clear

php artisan firefly:instructions install

exec apache2-foreground