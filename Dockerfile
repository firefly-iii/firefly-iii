FROM php:7-apache

RUN apt-get update -y && \
    apt-get install -y --no-install-recommends libcurl4-openssl-dev \
                                               zlib1g-dev \
                                               libjpeg62-turbo-dev \
                                               libpng12-dev \
                                               libicu-dev \
                                               libmcrypt-dev \
                                               libedit-dev \
                                               libtidy-dev \
                                               libxml2-dev \
                                               libsqlite3-dev \
                                               libbz2-dev && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j$(nproc) curl gd intl json mcrypt readline tidy zip bcmath xml mbstring pdo_sqlite pdo_mysql bz2

# Enable apache mod rewrite..
RUN a2enmod rewrite

# Setup the Composer installer
RUN curl -o /tmp/composer-setup.php https://getcomposer.org/installer && \
  curl -o /tmp/composer-setup.sig https://composer.github.io/installer.sig && \
  php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }" && \
  chmod +x /tmp/composer-setup.php && \
  php /tmp/composer-setup.php && \
  mv composer.phar /usr/local/bin/composer && \
  rm -f /tmp/composer-setup.{php,sig}

ADD . /var/www/firefly-iii
RUN chown -R www-data:www-data /var/www/
ADD docker/apache-firefly.conf /etc/apache2/sites-available/000-default.conf

USER www-data

WORKDIR /var/www/firefly-iii

RUN composer install --no-scripts --no-dev

USER root
