FROM php:8.2-apache

# Install and enable mysqli extension for MySQL database operations
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite module
RUN a2enmod rewrite

# Expose standard Apache port
EXPOSE 80
