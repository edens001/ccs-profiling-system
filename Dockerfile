# Dockerfile for CCS Profiling System
# Vue.js Frontend + PHP Backend API

# Stage 1: Build Vue.js Frontend
FROM node:18-alpine AS frontend-builder

WORKDIR /app

# Copy frontend package files
COPY frontend/package*.json ./
COPY frontend/package-lock.json* ./

# Install dependencies with legacy peer deps for Vue CLI compatibility
# Fix: Add npx and ensure proper permissions
RUN npm install --legacy-peer-deps

# Copy frontend source code
COPY frontend/ ./

# Fix: Use npx to run vue-cli-service or fix permissions
RUN chmod +x node_modules/.bin/vue-cli-service || true

# Build Vue.js app (outputs to 'dist' folder)
RUN npm run build

# Verify build output exists
RUN test -d dist && echo "Build successful" || (echo "Build failed - dist folder not found" && exit 1)

# Stage 2: Production Image with PHP/Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && \
    apt-get install -y \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    mysqli \
    pdo \
    pdo_mysql \
    mbstring \
    bcmath \
    gd

# Enable Apache modules
RUN a2enmod rewrite headers expires

# Copy backend files
COPY backend/ /var/www/html/backend/

# Copy built Vue.js frontend from builder stage
COPY --from=frontend-builder /app/dist /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    find /var/www/html -type d -exec chmod 755 {} \;

# Create .htaccess for Vue.js SPA routing
RUN cat > /var/www/html/.htaccess << 'EOF'
<IfModule mod_negotiation.c>
    Options -MultiViews
</IfModule>

<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    
    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    
    # Send Requests To Front Controller (index.html)
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.html [L]
</IfModule>

# Disable directory browsing
Options -Indexes
EOF

# Configure Apache virtual host
RUN cat > /etc/apache2/sites-available/000-default.conf << 'EOF'
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    
    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.html index.php
        FallbackResource /index.html
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# PHP configuration
RUN echo "upload_max_filesize = 20M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 20M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]