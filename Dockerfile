FROM php:7.1-apache

# If building on a RPi, use --build-arg cores=3 to use all cores when compiling
# to speed up the image build
ARG CORES
ENV CORES ${CORES:-1}

ENV FIREFLY_PATH /var/www/firefly-iii/
ENV CURL_VERSION 7.60.0
ENV OPENSSL_VERSION 1.1.1-pre6

LABEL version="1.0" maintainer="thegrumpydictator@gmail.com"


# install packages
RUN apt-get update -y && \
    apt-get install -y --no-install-recommends libcurl4-openssl-dev \
                                               zlib1g-dev \
                                               libjpeg62-turbo-dev \
                                               wget \
                                               libpng-dev \
                                               libicu-dev \
                                               libedit-dev \
                                               libtidy-dev \
                                               libxml2-dev \
                                               unzip \
                                               libsqlite3-dev \
                                               nano \
                                               libpq-dev \
                                               libbz2-dev \
                                               gettext-base \
                                               cron \
                                               rsyslog \
                                               supervisor \
                                               locales && \
                                               apt-get clean && \
                                               rm -rf /var/lib/apt/lists/*

# Install latest curl
RUN cd /tmp && \
    wget https://www.openssl.org/source/openssl-${OPENSSL_VERSION}.tar.gz && \
    tar -xvf openssl-${OPENSSL_VERSION}.tar.gz && \
    cd openssl-${OPENSSL_VERSION} && \
    ./config && \
    make -j${CORES} && \
    make install

RUN cd /tmp && \
    wget https://curl.haxx.se/download/curl-${CURL_VERSION}.tar.gz && \
    tar -xvf curl-${CURL_VERSION}.tar.gz && \
    cd curl-${CURL_VERSION} && \
    ./configure --with-ssl --host=$(gcc -dumpmachine) && \
    make -j${CORES} && \
    make install

# Make sure that libcurl is using the newer curl libaries
RUN echo "/usr/local/lib" >> /etc/ld.so.conf.d/00-curl.conf && ldconfig

# Mimic the Debian/Ubuntu config file structure for supervisor
COPY .deploy/docker/supervisord.conf /etc/supervisor/supervisord.conf
RUN mkdir -p /etc/supervisor/conf.d /var/log/supervisor

# copy Firefly III supervisor conf file.
COPY ./.deploy/docker/firefly-iii.conf /etc/supervisor/conf.d/firefly-iii.conf

# copy cron job supervisor conf file.
COPY ./.deploy/docker/cronjob.conf /etc/supervisor/conf.d/cronjob.conf

# test crons added via crontab
RUN echo "0 3 * * * /usr/local/bin/php /var/www/firefly-iii/artisan firefly:cron" | crontab -
#RUN (crontab -l ; echo "*/1 * * * * free >> /var/www/firefly-iii/public/cron.html") 2>&1 | crontab -
# Install PHP exentions.
RUN docker-php-ext-install -j$(nproc) gd intl tidy zip bcmath pdo_mysql bz2 pdo_pgsql

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Generate locales supported by Firefly III
RUN echo "en_US.UTF-8 UTF-8\nde_DE.UTF-8 UTF-8\nfr_FR.UTF-8 UTF-8\nit_IT.UTF-8 UTF-8\nnl_NL.UTF-8 UTF-8\npl_PL.UTF-8 UTF-8\npt_BR.UTF-8 UTF-8\nru_RU.UTF-8 UTF-8\ntr_TR.UTF-8 UTF-8\n\n" > /etc/locale.gen && locale-gen


# copy Apache config to correct spot.
COPY ./.deploy/docker/apache2.conf /etc/apache2/apache2.conf

# Enable apache mod rewrite..
RUN a2enmod rewrite

# Enable apache mod ssl..
RUN a2enmod ssl

# Create volumes
VOLUME $FIREFLY_PATH/storage/export $FIREFLY_PATH/storage/upload

# Enable default site (Firefly III)
COPY ./.deploy/docker/apache-firefly.conf /etc/apache2/sites-available/000-default.conf

# Make sure we own Firefly III directory
RUN chown -R www-data:www-data /var/www && chmod -R 775 $FIREFLY_PATH/storage

# Copy in Firefly Source
WORKDIR $FIREFLY_PATH
ADD . $FIREFLY_PATH

# Run composer
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN composer install --prefer-dist --no-dev --no-scripts --no-suggest

# Expose port 80
EXPOSE 80

# Run entrypoint thing
ENTRYPOINT [".deploy/docker/entrypoint.sh"]
