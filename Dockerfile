FROM php:8.2-apache

# Fix MPM error
RUN a2dismod mpm_event mpm_worker || true
RUN a2enmod mpm_prefork rewrite

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli

# Copy application
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80