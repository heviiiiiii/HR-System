FROM php:8.2-apache

# Copy your project files into Apache directory
COPY . /var/www/html/

# Give permissions (important for PHP apps sometimes)
RUN chown -R www-data:www-data /var/www/html

# Expose Apache port
EXPOSE 80
