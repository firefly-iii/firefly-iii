#!/usr/bin/env bash

# Install composer packages
composer install --no-suggest --no-scripts --no-ansi &> /dev/null

# Do static code analysis.
./vendor/bin/phpstan analyse -c .ci/phpstan.neon --no-progress --error-format=raw > phpstan.txt

cat phpstan.txt

exit 0