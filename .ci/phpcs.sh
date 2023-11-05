#!/usr/bin/env bash

#
# phpstan.sh
# Copyright (c) 2021 james@firefly-iii.org
#
# This file is part of Firefly III (https://github.com/firefly-iii).
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
#

# Install composer packages
#composer install --no-scripts --no-ansi

SCRIPT_DIR="$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

# enable test .env file.
# cp .ci/.env.ci .env

OUTPUT_FORMAT=txt
EXTRA_PARAMS="-v"

if [[ $GITHUB_ACTIONS = "true" ]]
then
    OUTPUT_FORMAT=gitlab
    EXTRA_PARAMS="--diff --dry-run"
fi

# clean up php code
cd $SCRIPT_DIR/php-cs-fixer
composer update --quiet
rm -f .php-cs-fixer.cache
PHP_CS_FIXER_IGNORE_ENV=true
./vendor/bin/php-cs-fixer fix \
    --config $SCRIPT_DIR/php-cs-fixer/.php-cs-fixer.php \
    --format=$OUTPUT_FORMAT \
    --allow-risky=yes $EXTRA_PARAMS

EXIT_CODE=$?

echo "Exit code for CS fixer is $EXIT_CODE."

cd $SCRIPT_DIR/..

exit $EXIT_CODE
