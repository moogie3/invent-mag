#!/bin/sh

# // NOTE: Run migrations on every deployment to ensure database schema is up-to-date
# // The --force flag is required when running migrations in production
php artisan migrate --force

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
