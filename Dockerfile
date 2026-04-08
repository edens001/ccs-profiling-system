FROM php:8.2-apache

# Install Node.js and npm
RUN apt-get update && \
    apt-get install -y nodejs npm && \
    rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite

# Copy everything
COPY . /var/www/html/

# Build frontend
RUN cd /var/www/html/frontend && \
    npm install && \
    npm run build && \
    cp -r dist/* /var/www/html/ 2>/dev/null || \
    cp -r build/* /var/www/html/ 2>/dev/null || \
    echo "Build completed"

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Disable directory listing
RUN echo 'Options -Indexes' > /var/www/html/.htaccess

# Set permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Remove source code (optional, for security)
RUN rm -rf /var/www/html/frontend/src /var/www/html/frontend/node_modules

EXPOSE 80