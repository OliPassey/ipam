# Use the official PHP 8.1 image with Apache.
FROM php:8.1-apache

# Install the PHP extensions we need and some utilities including unzip for Composer
RUN apt-get update && apt-get install -y unzip libpng-dev libjpeg-dev libfreetype6-dev libssl-dev libzip-dev nmap curl && \
    docker-php-ext-install pdo pdo_mysql gd zip && \
    pecl install mongodb && docker-php-ext-enable mongodb

# Set COMPOSER_ALLOW_SUPERUSER to 1
ENV COMPOSER_ALLOW_SUPERUSER 1

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy the application content to the container
COPY . .

# Install the PHP dependencies with Composer
RUN composer install --no-interaction

# Set permissions on out directory
RUN chown -R www-data:www-data /var/www/html/scans

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Expose port 80
EXPOSE 80

# Command to run Apache in the foreground
CMD ["apache2-foreground"]