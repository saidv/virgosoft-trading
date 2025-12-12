#!/bin/sh
set -e

echo "Starting Trading Platform..."

# Wait for database to be ready
echo "Waiting for database..."
max_tries=30
counter=0
until php artisan db:monitor --databases=pgsql > /dev/null 2>&1; do
    counter=$((counter + 1))
    if [ $counter -gt $max_tries ]; then
        echo "Database connection failed after $max_tries attempts"
        exit 1
    fi
    echo "   Attempt $counter/$max_tries - Database not ready, waiting..."
    sleep 2
done
echo "Database is ready!"

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Seed database if DB_SEED is set
if [ "${DB_SEED:-false}" = "true" ]; then
    echo "Seeding database..."
    php artisan db:seed --force
fi

# Cache configuration for production
echo "Optimizing for production..."
php artisan config:cache
php artisan route:cache
# Skip view:cache - pure API with no blade views

echo "Application ready!"

# Execute the main command (php-fpm)
exec "$@"
