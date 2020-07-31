#!/usr/bin/env bash

echo $PWD

# single line install command
composer global require hirak/prestissimo \
                        --no-plugins --no-scripts

./vendor/bin/phpstan analyse -c .ci/phpstan.neon
