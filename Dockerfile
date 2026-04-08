# Dockerfile
# Stage 1: Build Frontend
FROM node:18-alpine as frontend-build

WORKDIR /app/frontend
COPY frontend/package*.json ./
RUN npm install
COPY frontend/ ./
RUN npm run build

# Stage 2: Backend with PHP
FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy backend files
COPY backend/ /var/www/html/backend/

# Copy built frontend files
COPY --from=frontend-build /app/frontend/dist /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Configure Apache for SPA routing
RUN echo "<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    FallbackResource /index.html\n\
</Directory>" > /etc/apache2/conf-available/custom.conf && \
    a2enconf custom

# Enable mod_rewrite and mod_headers
RUN a2enmod rewrite headers

EXPOSE 80