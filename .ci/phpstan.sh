#!/usr/bin/env bash

echo $PWD

composer global require phpstan/phpstan
composer global require ergebnis/phpstan-rules
composer global require nunomaduro/larastan
composer global require phpstan/phpstan-deprecation-rules
composer global require thecodingmachine/phpstan-strict-rules
composer global require nette/coding-standard

~/.composer/vendor/bin/phpstan analyse -c .ci/phpstan.neon
