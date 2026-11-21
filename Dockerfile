# Demo portfolio: Apache + PHP 8.2 + MariaDB dalam satu container.
# Database dibuat ulang dari file .sql tiap container start (lihat deploy/start.sh).
FROM php:8.2-apache
RUN apt-get update \
 && apt-get install -y --no-install-recommends mariadb-server libzip-dev libpng-dev unzip \
 && docker-php-ext-install mysqli pdo_mysql zip gd \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY deploy/php-demo.ini /usr/local/etc/php/conf.d/zz-demo.ini
COPY deploy/legacy.php /usr/local/etc/php/legacy.php
WORKDIR /var/www/html
COPY . .
RUN if [ -f composer.json ] && [ ! -d vendor ]; then composer install --no-dev --no-interaction --no-progress; fi \
 && chown -R www-data:www-data /var/www/html
CMD ["bash", "deploy/start.sh"]
