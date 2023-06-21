#!/usr/bin/env bash

#
# phpmd.sh
# Copyright (c) 2023 james@firefly-iii.org
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

cd $SCRIPT_DIR/phpmd
composer update --quiet
./vendor/bin/phpmd \
  $SCRIPT_DIR/../app text phpmd.xml \
  --exclude $SCRIPT_DIR/../app/resources/** \
  --exclude $SCRIPT_DIR/../app/frontend/** \
  --exclude $SCRIPT_DIR/../app/public/** \
  --exclude $SCRIPT_DIR/../app/vendor/** \

cd $SCRIPT_DIR/..

exit 0
