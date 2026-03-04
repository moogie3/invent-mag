# ==============================================================================
# Stage 1: Build Frontend Assets
# Purpose: Compile JavaScript and CSS assets using Node.js/Vite.
# ==============================================================================
FROM node:18-alpine as frontend_build

WORKDIR /app

COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# ==============================================================================
# Stage 2: Prepare the PHP Application
# ==============================================================================
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    default-mysql-client \
    nginx \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libxpm-dev # // NOTE: Added dependencies for advanced GD support (needed for PDFs/Images)

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
# // NOTE: Configure and install GD with support for multiple formats
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy the entire application code
COPY . /var/www

# Copy compiled frontend assets
COPY --from=frontend_build /app/public/build /var/www/public/build

# Install PHP dependencies
# // NOTE: Set dummy DB env vars during build to prevent Laravel from trying to connect to a non-existent database
RUN DB_CONNECTION=sqlite DB_DATABASE=:memory: composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-req=php

# // NOTE: Configure Nginx - copy our custom config to replace the default one
COPY docker/nginx.conf /etc/nginx/sites-available/default

# // NOTE: Set up the startup script to handle multi-process execution (PHP-FPM + Nginx)
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# // NOTE: Railway handles port mapping, exposing 80 is standard for the container
EXPOSE 80

# // NOTE: Switch to our custom start script instead of just php-fpm
CMD ["/usr/local/bin/start.sh"]
