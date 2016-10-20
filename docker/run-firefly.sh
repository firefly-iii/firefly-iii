#!/bin/bash

sleep 3;
tables = 1
tables=`mysql --user=$MYSQL_USER --password=$MYSQL_PASSWORD --host=mysql --raw --batch $MYSQL_DATABASE -e 'SHOW TABLES;' | wc -l `

echo $tables

#sleep 20;
#php artisan migrate --seed --env=production --force
apache2ctl -DFOREGROUND
