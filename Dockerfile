# Use a standard, well-supported PHP-Apache image
FROM php:8.2-apache

# Set the working directory for the Apache server
WORKDIR /var/www/html

# Copy all of your project files from GitHub into the server's web directory
COPY . .
