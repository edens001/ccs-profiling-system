# Dockerfile - Install PostgreSQL PHP extension
FROM php:8.2-apache

# Install PostgreSQL PHP extensions (instead of MySQL)
RUN apt-get update && \
    apt-get install -y libpq-dev && \
    docker-php-ext-install pdo_pgsql pgsql mbstring bcmath

# Enable Apache modules
RUN a2enmod rewrite headers

# Copy backend files
COPY backend/ /var/www/html/backend/

# Copy frontend files
COPY frontend/dist/ /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Create .htaccess for Vue.js routing
RUN echo "RewriteEngine On" > /var/www/html/.htaccess && \
    echo "RewriteCond %{REQUEST_FILENAME} !-f" >> /var/www/html/.htaccess && \
    echo "RewriteCond %{REQUEST_FILENAME} !-d" >> /var/www/html/.htaccess && \
    echo "RewriteRule ^ index.html [L]" >> /var/www/html/.htaccess

EXPOSE 80