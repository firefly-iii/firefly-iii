#!/bin/bash
composer self-update
php artisan migrate:refresh --seed --env=local
./ide-helper.sh