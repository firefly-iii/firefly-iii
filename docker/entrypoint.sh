#!/bin/bash

cat .env.docker | envsubst > .env

exec apache2-foreground
