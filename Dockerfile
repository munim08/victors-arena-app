# Use the official PHP-Apache base image
FROM php:8.2-apache

# --- THIS IS THE FIX ---
# Install the mysqli PHP extension, which is required to connect to MySQL/PostgreSQL databases.
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
# --- END OF FIX ---

# Set the working directory for the Apache server
WORKDIR /var/www/html

# Copy all of your project files from GitHub into the server's web directory
COPY . .
