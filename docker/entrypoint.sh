#!/bin/bash


cat .env.docker | envsubst > .env && cat .env
composer dump-autoload
php artisan optimize
php artisan package:discover
php artisan firefly:instructions install
exec apache2-foreground
