#!/bin/bash

wait 10;
php artisan migrate --seed --env=production --force
apache2ctl -DFOREGROUND
