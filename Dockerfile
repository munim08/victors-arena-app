# Use the official PHP 8.2 with Apache base image
FROM php:8.2-apache

# Install system dependencies required for PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install -j$(nproc) pgsql pdo_pgsql mysqli

# Set the working directory for the Apache server
WORKDIR /var/www/html

# Copy all of your project files from GitHub into the server's web directory
COPY . .
