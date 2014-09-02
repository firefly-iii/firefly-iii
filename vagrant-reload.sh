#!/bin/sh
php artisan migrate:refresh --seed --env=vagrant
rm -f ./app/storage/logs/laravel.log;
rm -f ./app/storage/*.json
sudo service nginx restart
