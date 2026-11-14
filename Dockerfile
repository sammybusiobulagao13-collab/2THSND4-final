FROM php:8.2-cli-alpine

# Install system dependencies & PostgreSQL dev libraries
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    git \
    unzip \
    bash

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    intl \
    zip \
    opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy OPcache configuration
COPY docker/opcache.ini /usr/local/etc/php/conf.d/custom-opcache.ini

WORKDIR /app

# Copy application files
COPY . /app

# Ensure cache and log directories are writable
RUN mkdir -p var/cache var/log public && \
    chmod -R 777 var

EXPOSE 8000

# Start PHP built-in server for Symfony
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
