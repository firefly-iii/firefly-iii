#!/bin/bash

cat .env.docker | envsubst > .env

if [ "${INIT_DATABASE:="no"}" = "yes" ]; then
	echo "Init database detected, checking mysql status"
	# depends on your machine, but it may take a file to boot mysql container the first time
	until php artisan firefly:verify &>/dev/null
	do
  		echo "waiting mysql"
  		sleep 10 
	done
	php artisan migrate:refresh --seed
fi

exec apache2-foreground