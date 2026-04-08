# Dockerfile for PHP Backend API
FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite for routing
RUN a2enmod rewrite

# Copy all backend files to Apache's web directory
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Configure Apache to use .htaccess files
RUN echo "<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" > /etc/apache2/conf-available/custom.conf && \
    a2enconf custom

# Keep default document root - serve from root directory
# Your API should be accessible via /api/admin/ path

EXPOSE 80