FROM php:8.2-apache

RUN a2enmod rewrite

RUN docker-php-ext-install pdo pdo_mysql

RUN pecl install -o -f xdebug-3.4.5 \
    && docker-php-ext-enable xdebug

COPY php.ini /usr/local/etc/php/conf.d/

COPY . /var/www/html/
WORKDIR /var/www/html/

CMD ["apache2-foreground"]