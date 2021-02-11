#!/usr/bin/env bash

[ -d "~/Sites" ] && exit 1;

# build translations.
php /sites/FF3/dev/tools/cli.php ff3:json-translations v2

# remove old stuff
rm -rf public/
rm -rf ../public/fonts
rm -rf ../public/v2/js
rm -rf ../public/v2/css

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