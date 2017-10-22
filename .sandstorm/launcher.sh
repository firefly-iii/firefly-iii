#!/bin/bash

# Runs every time we create a new grain!

# Create a bunch of folders under the clean /var that php, nginx, and mysql expect to exist
mkdir -p /var/lib/mysql
mkdir -p /var/lib/nginx
mkdir -p /var/lib/php/sessions/
mkdir -p /var/log
mkdir -p /var/log/mysql
mkdir -p /var/log/nginx
# Wipe /var/run, since pidfiles and socket files from previous launches should go away
# TODO someday: I'd prefer a tmpfs for these.
rm -rf /var/run
mkdir -p /var/run
rm -rf /var/tmp
mkdir -p /var/tmp
mkdir -p /var/run/mysqld

# make storage directories
rm -rf /var/storage
mkdir -p /var/storage/app/public
mkdir -p /var/storage/build
mkdir -p /var/storage/database
mkdir -p /var/storage/debugbar
mkdir -p /var/storage/export
mkdir -p /var/storage/framework/cache
mkdir -p /var/storage/framework/sessions
mkdir -p /var/storage/framework/views
mkdir -p /var/storage/logs
mkdir -p /var/storage/upload


# Ensure mysql tables created
HOME=/etc/mysql /usr/bin/mysql_install_db --force

# Spawn mysqld, php
HOME=/etc/mysql /usr/sbin/mysqld &

/usr/sbin/php-fpm7.1 --nodaemonize --fpm-config /etc/php/7.1/fpm/php-fpm.conf &

# Wait until mysql and php have bound their sockets, indicating readiness
while [ ! -e /var/run/mysqld/mysqld.sock ] ; do
    echo "waiting for mysql to be available at /var/run/mysqld/mysqld.sock"
    sleep .5
done
while [ ! -e /var/run/php7.1-fpm.sock ] ; do
    echo "waiting for php7.1-fpm to be available at /var/run/php7.1-fpm.sock"
    sleep .5
done

echo "Installing database.."
# Install database for Firefly III
echo "CREATE DATABASE IF NOT EXISTS firefly; GRANT ALL on firefly.* TO 'firefly'@'localhost' IDENTIFIED BY 'firefly';" | mysql -uroot
echo "Done!"

echo "Migrating..."
php /opt/app/artisan migrate --seed --force
echo "Done!"

# Start nginx.
/usr/sbin/nginx -c /opt/app/.sandstorm/service-config/nginx.conf -g "daemon off;"
