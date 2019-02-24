FROM arm32v7/php:7.2.8-apache-stretch
ARG ARCH
COPY tmp/qemu-arm-static /usr/bin/qemu-arm-static
ENV FIREFLY_PATH=/var/www/firefly-iii COMPOSER_ALLOW_SUPERUSER=1
LABEL version="1.4" maintainer="thegrumpydictator@gmail.com"

# Create volumes
VOLUME $FIREFLY_PATH/storage/export $FIREFLY_PATH/storage/upload

# Install some stuff
RUN apt-get update && apt-get install -y libpng-dev \
                                            libicu-dev \
                                            unzip \
                                            gettext-base \
                                            libldap2-dev \
                                            libpq-dev \
                                            locales \
                                            libmemcached-dev

# Copy in Firefly III source
WORKDIR $FIREFLY_PATH
ADD . $FIREFLY_PATH

# copy ca certs to correct location
COPY ./.deploy/docker/cacert.pem /usr/local/ssl/cert.pem

# copy Apache config to correct spot.
COPY ./.deploy/docker/apache2.conf /etc/apache2/apache2.conf

# Enable default site (Firefly III)
COPY ./.deploy/docker/apache-firefly.conf /etc/apache2/sites-available/000-default.conf

# Run a lot of installation commands:
RUN chown -R www-data:www-data /var/www && \
    chmod -R 775 $FIREFLY_PATH/storage && \
    a2enmod rewrite && a2enmod ssl && \
    docker-php-ext-configure ldap --with-libdir=lib/$(gcc -dumpmachine)/ && \
    docker-php-ext-install -j$(nproc) zip bcmath ldap gd pdo_pgsql pdo_mysql intl opcache && \
    pecl install memcached-3.1.3 && \
    docker-php-ext-enable memcached && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    echo "de_DE.UTF-8 UTF-8\nen_US.UTF-8 UTF-8\nes_ES.UTF-8 UTF-8\nfr_FR.UTF-8 UTF-8\nid_ID.UTF-8 UTF-8\nit_IT.UTF-8 UTF-8\nnl_NL.UTF-8 UTF-8\npl_PL.UTF-8 UTF-8\npt_BR.UTF-8 UTF-8\nru_RU.UTF-8 UTF-8\ntr_TR.UTF-8 UTF-8\nzh_TW.UTF-8 UTF-8\nzh_CN.UTF-8 UTF-8\n\n" > /etc/locale.gen && \
    locale-gen && \
    composer install --prefer-dist --no-dev --no-scripts --no-suggest

# Expose port 80
EXPOSE 80

# Run entrypoint thing
ENTRYPOINT [".deploy/docker/entrypoint.sh"]
