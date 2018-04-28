#!/bin/bash

# make sure we own the volumes:
chown -R www-data:www-data -R $FIREFLY_PATH/storage/export $FIREFLY_PATH/storage/upload $FIREFLY_PATH/storage/logs $FIREFLY_PATH/storage/cache
chmod -R 775 $FIREFLY_PATH/storage/export $FIREFLY_PATH/storage/upload $FIREFLY_PATH/storage/upload $FIREFLY_PATH/storage/logs $FIREFLY_PATH/storage/cache

# remove any lingering files that may break upgrades:
rm -f $FIREFLY_PATH/storage/logs/laravel.log

cat .env.docker | envsubst > .env && cat .env
composer dump-autoload
php artisan package:discover
php artisan firefly:instructions install
exec apache2-foreground
