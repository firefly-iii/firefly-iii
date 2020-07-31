#!/usr/bin/env bash

echo $PWD

composer global require hirak/prestissimo --no-plugins --no-scripts
composer global require phpstan/phpstan --no-plugins --no-scripts
composer global require ergebnis/phpstan-rules --no-plugins --no-scripts
composer global require nunomaduro/larastan --no-plugins --no-scripts
composer global require phpstan/phpstan-deprecation-rules --no-plugins --no-scripts
composer global require thecodingmachine/phpstan-strict-rules --no-plugins --no-scripts
composer global require nette/coding-standard --no-plugins --no-scripts

~/.config/composer/vendor/bin/phpstan analyse -c .ci/phpstan.neon
