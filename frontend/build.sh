#!/usr/bin/env bash

#
# build.sh
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

[ -d "~/Sites" ] && exit 1;

# build translations.
php /sites/FF3/dev/tools/cli.php ff3:json-translations v2

# remove old stuff
rm -rf public/
rm -rf ../public/fonts
rm -rf ../public/v2/js
rm -rf ../public/v2/css
mkdir -p public/js
mkdir -p public/css

# build new stuff
yarn install
yarn audit fix
yarn upgrade
yarn prod

# yarn watch

# move to right directory
# mv public/js ../public/v2
# mv public/css ../public/v2

# also copy fonts
cp -r fonts ../public

# remove built stuff
rm -rf public
