#!/bin/bash
#composer self-update

rm -f ./app/storage/debugbar/*.json
rm -f ./app/storage/logs/laravel.log
rm -f ./app/storage/meta/services.json

for i in `seq 0 9`;
do
    rm -f ./app/storage/views/$i*
done

rm -f ./app/storage/views/a*
rm -f ./app/storage/views/b*
rm -f ./app/storage/views/c*
rm -f ./app/storage/views/d*
rm -f ./app/storage/views/e*
rm -f ./app/storage/views/f*

composer update
php artisan clear-compiled --env=local
php artisan ide-helper:generate --env=local
php artisan ide-helper:models --env=local --write
php artisan optimize --env=local
php artisan dump-autoload --env=local
