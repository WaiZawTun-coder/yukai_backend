FROM php:8.2-apache

# Ensure only one MPM is active and enable rewrite
RUN a2dismod mpm_worker mpm_prefork mpm_event || true \
    && a2enmod mpm_event rewrite

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
 && docker-php-ext-install curl pdo_mysql mysqli

# Copy project files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Use Railway PORT at runtime
CMD sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf \
 && sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-enabled/000-default.conf \
 && apache2-foreground