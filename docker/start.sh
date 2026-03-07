#!/bin/sh

# // NOTE: Wait for MySQL to be ready
# This prevents "Connection refused" errors if MySQL starts slower than the App
echo "Checking database connection: HOST=$DB_HOST PORT=${DB_PORT:-3306}"

# Wait up to 30 seconds for the database
MAX_RETRIES=15
COUNT=0

while [ $COUNT -lt $MAX_RETRIES ]; do
  if nc -z "$DB_HOST" "${DB_PORT:-3306}"; then
    echo "Database is ready!"
    break
  fi
  echo "Waiting for database ($COUNT/$MAX_RETRIES)..."
  COUNT=$((COUNT + 1))
  sleep 2
done

if [ $COUNT -eq $MAX_RETRIES ]; then
  echo "Warning: Could not connect to database after $MAX_RETRIES attempts. Proceeding anyway..."
fi

# // NOTE: Run migrations on every deployment to ensure database schema is up-to-date
php artisan migrate --force

# // NOTE: Ensure storage link exists for public access to uploaded files
php artisan storage:link --force

# // NOTE: Ensure log file exists and has correct permissions
touch /var/www/storage/logs/laravel.log
chown www-data:www-data /var/www/storage/logs/laravel.log
chmod 664 /var/www/storage/logs/laravel.log

# // NOTE: Ensure storage directories have correct permissions (run as www-data)
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/logs
mkdir -p /var/www/storage/app/public
mkdir -p /var/www/bootstrap/cache

# Fix permissions - ensure www-data owns everything
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 777 /var/www/storage /var/www/bootstrap/cache

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
