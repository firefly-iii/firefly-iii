#!/bin/bash

sleep 20;
php artisan migrate --seed --env=production --force
apache2ctl -DFOREGROUND
