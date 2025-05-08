FROM php:8.1-fpm

# Install PDO MySQL extension and other required extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Install additional dependencies if needed
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev

# Install PHP extensions
RUN docker-php-ext-install mbstring exif pcntl bcmath gd

# Set working directory
WORKDIR /var/www/html

# Set proper permissions
RUN chown -R www-data:www-data /var/www