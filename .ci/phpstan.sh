#!/usr/bin/env bash

# Install composer packages
composer install --no-suggest --no-scripts --no-ansi


# Do static code analysis.
./vendor/bin/phpstan analyse -c .ci/phpstan.neon --no-progress

exit 0