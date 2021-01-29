#!/usr/bin/env bash

[ -d "~/Sites" ] && exit 1;

# build translations.
php /sites/FF3/dev/tools/cli.php ff3:json-translations v2
