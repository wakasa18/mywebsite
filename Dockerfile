FROM php:8.3-apache

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libonig-dev \
    libpq-dev \
    && docker-php-ext-install \
        intl \
        mbstring \
        pgsql \
        pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite
RUN a2enmod rewrite

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy project
COPY . .

# Install production dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# CodeIgniter writable directory
RUN mkdir -p writable/cache writable/logs writable/session writable/uploads \
    && chown -R www-data:www-data writable \
    && chmod -R 775 writable

# Make CI4 public/ the Apache document root
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/*.conf

RUN sed -ri 's!<Directory /var/www/>!<Directory /var/www/html/public/>!g' \
    /etc/apache2/apache2.conf

EXPOSE 80