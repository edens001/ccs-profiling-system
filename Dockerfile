# Dockerfile
# Stage 1: Build Frontend
FROM node:18-alpine as frontend-build

# Set working directory
WORKDIR /app

# Copy frontend package files
COPY frontend/package*.json ./frontend/

# Install dependencies
WORKDIR /app/frontend
RUN npm ci --only=production || npm install

# Copy frontend source code
COPY frontend/ ./

# Build the frontend
RUN npm run build

# Stage 2: Backend with PHP
FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy backend files
COPY backend/ /var/www/html/backend/

# Copy built frontend files (check common output directories)
COPY --from=frontend-build /app/frontend/dist /var/www/html/
# If your build outputs to 'build' instead of 'dist', uncomment below:
# COPY --from=frontend-build /app/frontend/build /var/www/html/

# If frontend build outputs to root of frontend folder
COPY --from=frontend-build /app/frontend/*.html /var/www/html/ 2>/dev/null || true
COPY --from=frontend-build /app/frontend/*.css /var/www/html/ 2>/dev/null || true
COPY --from=frontend-build /app/frontend/*.js /var/www/html/ 2>/dev/null || true

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