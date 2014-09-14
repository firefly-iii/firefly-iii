#!/bin/bash
cd ..
composer self-update
composer update
cd scripts
rm -f ../app/storage/firefly-iii-import-*.json
rm -f ../app/storage/debugbar/*.json
rm -f ../app/storage/logs/larave*.log
rm -f ../app/storage/meta/services.json

for i in `seq 0 9`;
do
    rm -f ../app/storage/views/$i*
done

rm -f ../app/storage/views/a*
rm -f ../app/storage/views/b*
rm -f ../app/storage/views/c*
rm -f ../app/storage/views/d*
rm -f ../app/storage/views/e*
rm -f ../app/storage/views/f*


php ../artisan clear-compiled --env=local
php ../artisan ide-helper:generate --env=local
php ../artisan ide-helper:models --env=local --write
php ../artisan optimize --env=local
php ../artisan dump-autoload --env=local
./local-reset.sh
