FROM ubuntu:latest

RUN apt-get update && apt-get install -y apache2 php7.0 libapache2-mod-php7.0 wget php7.0-mbstring php7.0-xml php7.0-intl git php7.0-zip php7.0-bcmath php7.0-mysql

RUN cd ~ && wget https://getcomposer.org/download/1.0.2/composer.phar && chmod +x composer.phar && mv composer.phar /usr/local/bin/composer && composer selfupdate 

ADD docker/apache-firefly.conf /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite

ADD docker/run-firefly.sh /root/run-firefly.sh
RUN chmod +x /root/run-firefly.sh

ADD . /var/www/firefly-iii
RUN chown -R www-data:www-data /var/www/

USER www-data

WORKDIR /var/www/firefly-iii

RUN composer install --no-scripts --no-dev

RUN php artisan key:generate

USER root

CMD /root/run-firefly.sh
