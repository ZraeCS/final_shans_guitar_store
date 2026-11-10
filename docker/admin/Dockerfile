# Shan's Guitar - admin / staff panel (Symfony 8)
# Apache + PHP 8.4 with the extensions Symfony needs. Replaces
# "symfony server:start" and XAMPP's PHP.
FROM php:8.4-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends libicu-dev libzip-dev unzip git \
 && docker-php-ext-install intl pdo_mysql zip opcache \
 && rm -rf /var/lib/apt/lists/*

# Composer (to run "composer install" inside the container)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_HOME=/tmp/composer

COPY php.ini /usr/local/etc/php/conf.d/zz-shansguitar.ini
COPY vhost.conf /etc/apache2/sites-available/000-default.conf

# Apache runs as www-data (not root) on port 8080 inside the container.
# Then "docker compose exec" commands also run as www-data, so the cache
# files they create can always be used by the website (no permission errors).
RUN sed -i 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
 && mkdir -p /var/www/admin/var \
 && chown -R www-data:www-data /var/www/admin /var/run/apache2 /var/lock/apache2 /var/log/apache2

WORKDIR /var/www/admin
USER www-data
EXPOSE 8080
