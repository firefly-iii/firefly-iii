#!/bin/bash
#composer self-update
composer update
php artisan clear-compiled --env=local
php artisan ide-helper:generate --env=local
php artisan ide-helper:models --env=local --write
php artisan optimize --env=local
php artisan dump-autoload --env=local
