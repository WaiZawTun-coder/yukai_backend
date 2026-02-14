FROM php:8.2-apache

# Disable all MPMs first, then enable only prefork
RUN a2dismod mpm_prefork mpm_event mpm_worker || true
RUN a2enmod mpm_prefork

RUN a2enmod rewrite
RUN docker-php-ext-install mysqli pdo pdo_mysql

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80