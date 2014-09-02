#!/bin/bash
#composer self-update
composer update
php artisan clear-compiled --env=vagrant
php artisan ide-helper:generate --env=vagrant
php artisan ide-helper:models --env=vagrant --write
php artisan optimize --env=vagrant
php artisan dump-autoload --env=vagrant