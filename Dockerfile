# ==============================================================================
# Stage 1: Build Frontend Assets
# Purpose: Compile JavaScript and CSS assets using Node.js/Vite.
# This stage is separate to keep the final image small (no Node.js in production).
# ==============================================================================
FROM node:18-alpine as frontend_build

WORKDIR /app

# Copy package.json and package-lock.json first to leverage Docker cache
COPY package*.json ./

# Install dependencies (including devDependencies needed for build)
# 'npm ci' is faster and more reliable than 'npm install' for CI/CD
RUN npm ci

# Copy the rest of the application code
COPY . .

# Run the production build (Vite)
# This generates minified assets in public/build/
RUN npm run build

# ==============================================================================
# Stage 2: Prepare the PHP Application
# Purpose: Set up the PHP-FPM environment, install dependencies, and finalize the image.
# ==============================================================================
FROM php:8.2-fpm

# Install system dependencies required for Laravel and PHP extensions
# - libpng-dev, libonig-dev, libxml2-dev, libzip-dev: Libraries for PHP extensions
# - zip, unzip: For Composer
# - default-mysql-client: Useful for debugging database connections
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    default-mysql-client

# Clear local repository cache to reduce image size
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by Laravel
# - pdo_mysql: For MySQL database connection
# - mbstring: For string manipulation
# - exif: For image metadata
# - pcntl: For process control (queues)
# - bcmath: For precise floating-point mathematics
# - gd: For image processing
# - zip: For zip file handling
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Get latest Composer binary from the official Composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory inside the container
WORKDIR /var/www

# Copy the entire application code to the container
COPY . /var/www

# Copy compiled frontend assets from Stage 1 (frontend_build)
# This places the built CSS/JS files into the public directory
COPY --from=frontend_build /app/public/build /var/www/public/build

# Install PHP dependencies via Composer
# - --no-interaction: Do not ask for user input
# - --prefer-dist: Download archives instead of cloning repos
# - --optimize-autoloader: Optimize the autoloader for performance
# - --no-dev: Do not install development dependencies (phpunit, mockery, etc.)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Set permissions for the web server user (www-data)
# Ensure storage and bootstrap/cache are writable
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Start PHP-FPM server
CMD ["php-fpm"]
