FROM webdevops/php-nginx:7.2

ENV FIREFLY_PATH /app
WORKDIR $FIREFLY_PATH
ADD . $FIREFLY_PATH

# gettext is used to update the .env file when the container launches.
RUN apt-get update -y && apt-get install -y --no-install-recommends gettext-base && apt-get clean

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Generate locales supported by Firefly III
RUN echo "en_US.UTF-8 UTF-8\nde_DE.UTF-8 UTF-8\nfr_FR.UTF-8 UTF-8\nit_IT.UTF-8 UTF-8\nnl_NL.UTF-8 UTF-8\npl_PL.UTF-8 UTF-8\npt_BR.UTF-8 UTF-8\nru_RU.UTF-8 UTF-8\ntr_TR.UTF-8 UTF-8\n\n" > /etc/locale.gen && locale-gen

# Create volumes
VOLUME $FIREFLY_PATH/storage/export $FIREFLY_PATH/storage/upload

# Make sure we own Firefly III directory
RUN chown -R $APPLICATION_GID:$APPLICATION_UID /var/www && chmod -R 775 $FIREFLY_PATH/storage

# Add cron job
RUN docker-service enable cron
RUN docker-cronjob '0 3 * * * application cd /app/ && php artisan firefly:cron'

# Run composer
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN composer install --prefer-dist --no-dev --no-scripts --no-suggest

# Copy nginx config to correct spot.
COPY ./.deploy/docker/vhost.conf /opt/docker/etc/nginx/vhost.conf

# Copy entrypoint script to correct spot:
COPY ./.deploy/docker/entrypoint.sh /opt/docker/provision/entrypoint.d/default.sh
