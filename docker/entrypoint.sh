#!/bin/sh
set -e

# Wait for PostgreSQL to be healthy with retry limit
DB_HOST="${DB_HOST:-postgres}"
DB_PORT="${DB_PORT:-5432}"
DB_USERNAME="${DB_USERNAME:-helpdesk_user}"

echo "Waiting for PostgreSQL database at ${DB_HOST}:${DB_PORT}..."
MAX_TRIES=30
COUNT=0
until pg_isready -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}"; do
  COUNT=$((COUNT+1))
  if [ "$COUNT" -ge "$MAX_TRIES" ]; then
    echo "ERROR: PostgreSQL is unreachable at ${DB_HOST}:${DB_PORT} after 60 seconds."
    echo "Check if the postgres container is running: docker logs helpdesk_postgres_prod"
    exit 1
  fi
  echo "PostgreSQL is unavailable - sleeping 2s (attempt $COUNT/$MAX_TRIES)..."
  sleep 2
done
echo "PostgreSQL is up and accepting connections."

# Generate application key if missing
if [ -z "${APP_KEY}" ]; then
  echo "APP_KEY is not set. Generating application key..."
  php artisan key:generate --force || true
fi

# Fix storage permissions if writable check fails
if [ ! -w /var/www/html/storage ]; then
  chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true
fi

# Create public storage symlink if missing
if [ ! -L /var/www/html/public/storage ]; then
  echo "Linking storage to public..."
  php artisan storage:link || true
fi

# Run database migrations if enabled (default: true)
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  echo "Running database migrations..."
  php artisan migrate --force || true
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
