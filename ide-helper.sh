#!/bin/bash
php artisan clear-compiled --env=local
php artisan ide-helper:generate --env=local
php artisan optimize --env=local
