#!/bin/bash

# When you change this file, you must take manual action. Read this doc:
# - https://docs.sandstorm.io/en/latest/vagrant-spk/customizing/#setupsh

set -euo pipefail

export DEBIAN_FRONTEND=noninteractive

# install packages so we can install apt-add-repository.
apt-get update
apt-get install -y python-software-properties software-properties-common

# actually add repository
apt-key adv --keyserver keyserver.ubuntu.com --recv-keys E9C74FEEA2098A6E
add-apt-repository "deb http://packages.dotdeb.org jessie all"

# install packages.
apt-get update
apt-get install -y nginx php7.0-fpm php7.0-mysql php7.0-cli php7.0-curl git php7.0-dev php7.0-intl php7.0-dom php7.0-mbstring php7.0-bcmath mysql-server
service nginx stop
service php7.0-fpm stop
service mysql stop
systemctl disable nginx
systemctl disable php7.0-fpm
systemctl disable mysql
# patch /etc/php/7.0/fpm/pool.d/www.conf to not change uid/gid to www-data
sed --in-place='' \
       --expression='s/^listen.owner = www-data/;listen.owner = www-data/' \
       --expression='s/^listen.group = www-data/;listen.group = www-data/' \
       /etc/php/7.0/fpm/pool.d/www.conf
# patch /etc/php/7.0/fpm/php-fpm.conf to not have a pidfile
sed --in-place='' \
       --expression='s/^pid =/;pid =/' \
       /etc/php/7.0/fpm/php-fpm.conf

# move sock file to better dir:
sed --in-place='' \
       --expression='s/^listen = \/run\/php\/php7.0-fpm.sock/listen = \/var\/run\/php7.0-fpm.sock/' \
       /etc/php/7.0/fpm/pool.d/www.conf

# patch /etc/php/7.0/fpm/pool.d/www.conf to no clear environment variables
# so we can pass in SANDSTORM=1 to apps
sed --in-place='' \
       --expression='s/^;clear_env = no/clear_env=no/' \
       /etc/php/7.0/fpm/pool.d/www.conf
# patch mysql conf to not change uid, and to use /var/tmp over /tmp
# for secure-file-priv see https://github.com/sandstorm-io/vagrant-spk/issues/195
sed --in-place='' \
        --expression='s/^user\t\t= mysql/#user\t\t= mysql/' \
        --expression='s,^tmpdir\t\t= /tmp,tmpdir\t\t= /var/tmp,' \
        --expression='/\[mysqld]/ a\ secure-file-priv = ""\' \
        /etc/mysql/my.cnf
# patch mysql conf to use smaller transaction logs to save disk space
cat <<EOF > /etc/mysql/conf.d/sandstorm.cnf
[mysqld]
# Set the transaction log file to the minimum allowed size to save disk space.
# innodb_log_file_size = 1048576
# Set the main data file to grow by 1MB at a time, rather than 8MB at a time.
innodb_autoextend_increment = 1
EOF
