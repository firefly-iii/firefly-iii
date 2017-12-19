#!/bin/bash

# make sure we own the volumes:
chown -R www-data:www-data $FIREFLY_PATH/storage/export $FIREFLY_PATH/storage/upload
chmod -R 775 $FIREFLY_PATH/storage/export $FIREFLY_PATH/storage/upload

cat .env.docker | envsubst > .env && cat .env
composer dump-autoload
php artisan optimize
php artisan package:discover
php artisan firefly:instructions install
exec apache2-foreground
