# use PHP 7.1 and Apache as a base.
FROM php:7.1-apache

# set working dir
ENV FIREFLY_PATH /var/www/firefly-iii
WORKDIR $FIREFLY_PATH
ADD . $FIREFLY_PATH

# install packages
RUN apt-get update -y && \
    apt-get install -y --no-install-recommends libcurl4-openssl-dev \
                                               zlib1g-dev \
                                               libjpeg62-turbo-dev \
                                               libpng12-dev \
                                               libicu-dev \
                                               libedit-dev \
                                               libtidy-dev \
                                               libxml2-dev \
                                               libsqlite3-dev \
                                               libpq-dev \
                                               libbz2-dev \
                                               gettext-base \
                                               locales && \
                                               apt-get clean && \
                                               rm -rf /var/lib/apt/lists/*

# Install PHP exentions.
RUN docker-php-ext-install -j$(nproc) curl gd intl json readline tidy zip bcmath xml mbstring pdo_sqlite pdo_mysql bz2 pdo_pgsql

# Generate locales supported by Firefly III
RUN echo "de_DE.UTF-8 UTF-8\nen_US.UTF-8 UTF-8\nfr_FR.UTF-8 UTF-8\nid_ID.UTF-8 UTF-8\nnl_NL.UTF-8 UTF-8\npl_PL.UTF-8 UTF-8" > /etc/locale.gen && locale-gen

# copy Apache config to correct spot.
COPY ./docker/apache2.conf /etc/apache2/apache2.conf

# Enable apache mod rewrite..
RUN a2enmod rewrite

# Enable apache mod ssl..
RUN a2enmod ssl

# Create volumes for several directories:
VOLUME $FIREFLY_PATH/storage/export $FIREFLY_PATH/storage/upload

# Setup the Composer installer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Enable default site (Firefly III)
COPY ./docker/apache-firefly.conf /etc/apache2/sites-available/000-default.conf

# Make sure we own Firefly III directory
RUN chown -R www-data:www-data /var/www && chmod -R 775 $FIREFLY_PATH/storage

# Run composer
RUN composer install --prefer-dist --no-dev --no-scripts --no-suggest

# Expose port 80
EXPOSE 80

# Run entrypoint thing
ENTRYPOINT ["docker/entrypoint.sh"]