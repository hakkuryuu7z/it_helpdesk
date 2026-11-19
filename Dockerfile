# ==========================================
# Stage 1: Build Frontend Assets
# ==========================================
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package*.json vite.config.js ./
RUN npm ci
COPY resources resources
COPY public public
RUN npm run build

# ==========================================
# Stage 2: Production PHP-FPM Application
# ==========================================
FROM php:8.2-fpm-alpine AS app

# Copy official pre-compiled PHP extension installer script
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install system dependencies and fast PHP extensions (lean & blazing fast build for VPS)
RUN apk add --no-cache \
    postgresql-client \
    bash \
    su-exec \
    && install-php-extensions \
        pdo_pgsql \
        pgsql \
        bcmath \
        zip \
        opcache \
        pcntl

# Install Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy custom PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application source
COPY . .

# Copy compiled frontend assets from Stage 1
COPY --from=frontend /app/public/build ./public/build

# Finish Composer Autoload Optimization
RUN composer dump-autoload --optimize --no-dev

# Setup entrypoint and adjust directory permissions for www-data
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["php-fpm"]
