#!/usr/bin/env bash

FF_DIR=$PWD

# single line install command
composer global require hirak/prestissimo \
                        --no-plugins --no-scripts

cd $FF_DIR

echo "Changed back to '$FF_DIR'"

./vendor/bin/phpstan analyse -c .ci/phpstan.neon
