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
composer install --no-scripts --no-ansi

# enable test .env file.
cp .ci/.env.ci .env

# Do static code analysis.
./vendor/bin/phpstan analyse -c .ci/phpstan.neon --no-progress

exit 0