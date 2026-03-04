#!/bin/sh

# // NOTE: Wait for MySQL to be ready
# This prevents "Connection refused" errors if MySQL starts slower than the App
while ! nc -z $DB_HOST ${DB_PORT:-3306}; do
  echo "Waiting for MySQL at $DB_HOST..."
  sleep 2
done

# // NOTE: Run migrations on every deployment to ensure database schema is up-to-date
php artisan migrate --force

# // NOTE: Ensure storage link exists for public access to uploaded files
php artisan storage:link --force

# // NOTE: Clear and cache configuration to optimize performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# // NOTE: Start PHP-FPM in the background (-D flag)
php-fpm -D

# // NOTE: Start a queue worker in the background
# // The queue handles asynchronous tasks like sending emails
php artisan queue:work --daemon --tries=3 &

# // NOTE: Start Nginx in the foreground so the container doesn't exit
nginx -g "daemon off;"
