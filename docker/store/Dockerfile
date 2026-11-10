# Shan's Guitar - customer store (plain PHP)
# Apache + PHP 8.4, the same job XAMPP's Apache did.
FROM php:8.4-apache

# The store talks to MySQL through PDO
RUN docker-php-ext-install pdo_mysql

# Manila time, and room for photo uploads
COPY php.ini /usr/local/etc/php/conf.d/zz-shansguitar.ini

# http://localhost:8080 -> http://localhost:8080/shans_guitar/
# (the store's folder is mounted at /var/www/html/shans_guitar by compose.yaml)
RUN printf '<?php\nheader("Location: /shans_guitar/");\n' > /var/www/html/index.php
