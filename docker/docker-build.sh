#!/bin/bash

cd ${BASH_SOURCE%/*}

if ! [ -e 'secret_key' ]
then
 cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1 > secret_key
fi

key=`cat secret_key`

sed -i "s/SomeRandomStringOf32CharsExactly/$key/" ../.env

cd .. && sh -ac '. ./.env; envsubst < docker-compose.yml.tmpl > docker-compose.yml' && docker-compose build
