# =============================================================================
# Trading Platform - Production Dockerfile
# Multi-stage build for Laravel 12 with PostgreSQL
# =============================================================================

# =============================================================================
# Stage 1: Composer Dependencies
# =============================================================================
FROM composer:2.7 AS vendor

WORKDIR /app

COPY backend/composer.json backend/composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# =============================================================================
# Stage 2: Production Image
# =============================================================================
FROM php:8.4-fpm-alpine AS production

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    supervisor \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip opcache \
    && rm -rf /var/cache/apk/*

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Set working directory
WORKDIR /var/www

# Copy PHP configuration from infra
COPY infra/php/php.ini /usr/local/etc/php/conf.d/php.ini
COPY infra/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy entrypoint script
COPY infra/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copy application code
COPY --chown=www-data:www-data backend/ /var/www/

# Copy vendor from composer stage
COPY --from=vendor --chown=www-data:www-data /app/vendor /var/www/vendor

# Set proper permissions and clear any dev caches
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache \
    && rm -rf /var/www/bootstrap/cache/*.php

# Expose port 9000
EXPOSE 9000

# Use entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
