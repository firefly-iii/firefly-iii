#!/usr/bin/env bash

#
# phpunit.sh
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
SCRIPT_DIR="$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
# enable test .env file.
cp $SCRIPT_DIR/../.env $SCRIPT_DIR/../.env.backup
cp $SCRIPT_DIR/.env.ci $SCRIPT_DIR/../.env

COVERAGE=false
RESET=false
FILE=storage/database/database.sqlite

while getopts "cr" o; do
    case "${o}" in
        c) COVERAGE=true;;
        r) RESET=true;;
    esac
done

# reset if necessary.
if [ $RESET = "true" ] ; then
    rm -f $FILE
fi

# download test database
if [ -f "$FILE" ]; then
  echo 'DB exists, will use it'
else
  echo 'Download new DB'
  wget --quiet https://github.com/firefly-iii/test-fixtures/raw/main/test-database.sqlite -O $FILE
fi

# run phpunit
if [ $COVERAGE = "true" ] ; then
  echo 'Run with coverage'
  XDEBUG_MODE=coverage ./vendor/bin/phpunit --configuration phpunit.xml --coverage-html $SCRIPT_DIR/coverage
else
  echo 'Run without coverage'
  ./vendor/bin/phpunit --configuration phpunit.xml
fi

# restore .env file
mv $SCRIPT_DIR/../.env.backup $SCRIPT_DIR/../.env

cd $SCRIPT_DIR/..
