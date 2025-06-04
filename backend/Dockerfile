FROM php:7.4-fpm

RUN apt-get update \
 && apt-get install -y libpq-dev msmtp ca-certificates \
 && docker-php-ext-install pdo_pgsql

RUN pecl install xdebug-2.9.8 \
 && docker-php-ext-enable xdebug

RUN echo "xdebug.mode=debug"         >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo "xdebug.client_port=9003"   >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo "xdebug.log_level=0"        >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo "xdebug.log=/tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN { \
      echo 'sendmail_path = "/usr/bin/msmtp -t"'; \
      echo 'mail.log = "/var/log/php_mail.log"'; \
    } >> /usr/local/etc/php/php.ini

COPY backend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY backend /var/www
WORKDIR /var/www

EXPOSE 9000 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
