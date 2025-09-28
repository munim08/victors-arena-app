# Use the official Render PHP-Apache base image
FROM render/php-apache

# Tell the server where your public website files are
# This matches the "Start Command" we tried to use before
ENV DOCUMENT_ROOT /var/www/html/public_html

# Copy all of your project files from GitHub into the server's web directory
COPY . /var/www/html/public_html/
