FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

# Move code to /var/www and symlink public to Apache docroot
WORKDIR /var/www

COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

