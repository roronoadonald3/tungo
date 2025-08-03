FROM php:8.1-apache

# Install SQLite PDO extension
RUN docker-php-ext-install pdo_sqlite

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy application code
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Expose port
EXPOSE 8080

# Start Apache in foreground
CMD ["apache2-foreground"]
