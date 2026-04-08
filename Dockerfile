FROM php:8.2-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite

# Copy backend files
COPY backend/ /var/www/html/backend/

# Copy frontend built files (if you have them)
COPY frontend/dist/ /var/www/html/ 2>/dev/null || true
COPY frontend/build/ /var/www/html/ 2>/dev/null || true

# FIX PERMISSIONS - This solves the Forbidden error
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 644 /var/www/html/*.html 2>/dev/null || true && \
    find /var/www/html -type d -exec chmod 755 {} \;

# Create a test index.html if none exists
RUN echo '<!DOCTYPE html>
<html>
<head>
    <title>CCS Profiling System</title>
    <style>
        body { font-family: Arial; text-align: center; padding: 50px; }
        h1 { color: #333; }
        .error { color: red; }
        .info { color: green; }
    </style>
</head>
<body>
    <h1>CCS Profiling System</h1>
    <p class="info">Server is running!</p>
    <p>If you see this, Apache is working correctly.</p>
    <p>Check your frontend build: <strong>/var/www/html/</strong></p>
</body>
</html>' > /var/www/html/index.html

# Configure Apache correctly
RUN echo '<Directory /var/www/html>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
    DirectoryIndex index.html index.php
</Directory>' > /etc/apache2/conf-available/custom.conf && \
    a2enconf custom

EXPOSE 80