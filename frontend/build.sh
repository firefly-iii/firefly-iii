#!/usr/bin/env bash

# build translations.
php /sites/tools/firefly-iii-tools/cli.php ff3:json-translations --v2

# remove old stuff
rm -rf public/
rm -rf ../public/fonts
rm -rf ../public/v2/js
rm -rf ../public/v2/css

# build new stuff
npm install
npm audit fix
npm upgrade
npm run prod

# npm run watch-poll

# move to right directory
# mv public/js ../public/v2
# mv public/css ../public/v2

# also copy fonts
cp -r fonts ../public
