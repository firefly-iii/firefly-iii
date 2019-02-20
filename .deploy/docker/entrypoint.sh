#!/bin/bash

echo "Now in entrypoint.sh for Firefly III"

lscpu

# make sure the correct directories exists (suggested by @chrif):
echo "Making directories..."
mkdir -p $FIREFLY_PATH/storage/app/public
mkdir -p $FIREFLY_PATH/storage/build
mkdir -p $FIREFLY_PATH/storage/database
mkdir -p $FIREFLY_PATH/storage/debugbar
mkdir -p $FIREFLY_PATH/storage/export
mkdir -p $FIREFLY_PATH/storage/framework/cache/data
mkdir -p $FIREFLY_PATH/storage/framework/sessions
mkdir -p $FIREFLY_PATH/storage/framework/testing
mkdir -p $FIREFLY_PATH/storage/framework/views/v1
mkdir -p $FIREFLY_PATH/storage/framework/views/v2
mkdir -p $FIREFLY_PATH/storage/logs
mkdir -p $FIREFLY_PATH/storage/upload


echo "Touch DB file (if SQLlite)..."
if [[ $DB_CONNECTION == "sqlite" ]]
then
    touch $FIREFLY_PATH/storage/database/database.sqlite
    echo "Touched!"
fi

if [[ $FF_DB_CONNECTION == "sqlite" ]]
then
    touch $FIREFLY_PATH/storage/database/database.sqlite
    echo "Touched!"
fi

# make sure we own the volumes:
echo "Run chown on ${FIREFLY_PATH}/storage..."
chown -R www-data:www-data -R $FIREFLY_PATH/storage
echo "Run chmod on ${FIREFLY_PATH}/storage..."
chmod -R 775 $FIREFLY_PATH/storage

# remove any lingering files that may break upgrades:
echo "Remove log file..."
rm -f $FIREFLY_PATH/storage/logs/laravel.log

echo "Map environment variables on .env file..."
cat $FIREFLY_PATH/.deploy/docker/.env.docker | envsubst > $FIREFLY_PATH/.env
echo "Dump auto load..."
composer dump-autoload
echo "Discover packages..."
php artisan package:discover

echo "Run various artisan commands..."
php artisan migrate --seed
php artisan firefly:decrypt-all
php artisan firefly:upgrade-database
php artisan firefly:verify
php artisan passport:install
php artisan cache:clear

php artisan firefly:instructions install

echo "Go!"
exec apache2-foreground