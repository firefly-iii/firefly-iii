#!/usr/bin/env bash

echo $PWD

# single line install command
composer global require hirak/prestissimo \
                        phpstan/phpstan \
                        ergebnis/phpstan-rules \
                        nunomaduro/larastan \
                        phpstan/phpstan-deprecation-rules \
                        thecodingmachine/phpstan-strict-rules \
                        nette/coding-standard \
                        --no-plugins --no-scripts

~/.config/composer/vendor/bin/phpstan analyse -c .ci/phpstan.neon
