#!/bin/sh
set -e

# Wait for PostgreSQL to be healthy
echo "Waiting for PostgreSQL database at ${DB_HOST}:${DB_PORT}..."
until pg_isready -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}"; do
  echo "PostgreSQL is unavailable - sleeping 2s"
  sleep 2
done
echo "PostgreSQL is up and accepting connections."

# Fix storage permissions if writable check fails
if [ ! -w /var/www/html/storage ]; then
  chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true
fi

# In production mode, optimize configurations
if [ "${APP_ENV}" = "production" ]; then
  echo "Caching Laravel configuration, routes, and events for production..."
  php artisan config:cache || true
  php artisan route:cache || true
  php artisan view:cache || true
  php artisan event:cache || true
fi

exec "$@"
