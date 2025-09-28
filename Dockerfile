# Use the official PHP 8.2 with Apache base image
FROM php:8.2-apache

# Install system dependencies and PHP extensions required for MySQL/PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install -j$(nproc) pgsql pdo_pgsql mysqli \
    && a2enmod rewrite

# Set the working directory for the Apache server
WORKDIR /var/www/html

# Copy all of your project files from GitHub into the server's web directory
COPY . .
