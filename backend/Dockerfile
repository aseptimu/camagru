FROM php:7.4-fpm

RUN apt-get update \
 && apt-get install -y libpq-dev nginx \
 && docker-php-ext-install pdo_pgsql

COPY nginx/default.conf /etc/nginx/sites-available/default

COPY backend/src    /var/www/html/src
COPY backend/public /var/www/html/public

WORKDIR /var/www/html

EXPOSE 80 9000

CMD service nginx start && php-fpm
