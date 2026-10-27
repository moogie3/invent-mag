# ==============================================================================
# Stage 1: Build Frontend Assets
# Purpose: Compile JavaScript and CSS assets using Node.js/Vite.
# ==============================================================================
ARG CACHE_BUST=2

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
    libxpm-dev \
    libicu-dev \
    libcurl4-openssl-dev \
    netcat-openbsd # // NOTE: Added netcat (nc) for the database wait loop in start.sh

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
# // NOTE: Configure and install GD with support for multiple formats
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
# // NOTE: Added intl, xml, dom, simplexml, xmlwriter required by updated packages
# // NOTE: curl is pre-bundled in php:8.2-fpm base image, no need to compile it
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl xml dom simplexml xmlwriter

# // NOTE: OPcache holds compiled bytecode in memory so each request skips the file
# // read, the parse and the compile. This is the largest single per-request saving
# // available in production. Guarded because some php:*-fpm tags already ship it.
RUN if ! php -m | grep -qi '^opcache$'; then docker-php-ext-install opcache; fi

# // NOTE: Production opcache tuning.
# // validate_timestamps=0 never re-checks a file's mtime. That is safe here only
# // because every deploy runs `docker-compose pull` + `up -d`, which replaces the
# // container and therefore the cache: application code is baked into the image, and
# // the deploy script re-runs view:cache/route:cache into an empty cache afterwards.
# // If deploys ever stop recreating the container, set this to 1.
RUN printf '%s\n' \
    'opcache.enable=1' \
    'opcache.enable_cli=0' \
    'opcache.memory_consumption=192' \
    'opcache.interned_strings_buffer=16' \
    'opcache.max_accelerated_files=20000' \
    'opcache.validate_timestamps=0' \
    'opcache.save_comments=1' \
    > /usr/local/etc/php/conf.d/opcache-tuning.ini

# Set PHP memory limit
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini
RUN echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/memory-limit.ini

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
RUN DB_CONNECTION=sqlite DB_DATABASE=:memory: APP_KEY=base64:$(php -r "echo base64_encode(random_bytes(32));") composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs

# // NOTE: Configure Nginx - copy our custom config to replace the default one
COPY docker/nginx.conf /etc/nginx/sites-available/default

# // NOTE: Set up the startup script to handle multi-process execution (PHP-FPM + Nginx)
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Set permissions - create all necessary storage and cache directories
RUN mkdir -p /var/www/storage/logs \
    /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/app/public \
    /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# // NOTE: Railway handles port mapping, exposing 80 is standard for the container
EXPOSE 80

# // NOTE: Switch to our custom start script instead of just php-fpm
CMD ["/usr/local/bin/start.sh"]
