#!/bin/bash

cd ${BASH_SOURCE%/*}

# If secret key file does not exist then create it
if ! [ -e 'secret_key' ]
then
 cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1 > secret_key
fi

key=`cat secret_key`

# Replace key if not changed by anyone else
sed -i "s/SomeRandomStringOf32CharsExactly/$key/" ../.env

# Change DB Host to mysql 
sed -i "s/DB_HOST=127.0.0.1/mysql/" ../.env

cd .. && sh -ac '. ./.env; envsubst < docker-compose.yml.tmpl > docker-compose.yml' && docker-compose build
