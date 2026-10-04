FROM php:8.3-apache

# Install system dependencies
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

# Ensure ONLY prefork MPM is enabled
RUN a2dismod mpm_event || true \
    && a2dismod mpm_worker || true \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy CodeIgniter project
COPY . .

# Install PHP dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Writable permissions
RUN mkdir -p \
        writable/cache \
        writable/logs \
        writable/session \
        writable/uploads \
    && chown -R www-data:www-data writable \
    && chmod -R 775 writable

# Point Apache document root to CodeIgniter public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri \
    's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

EXPOSE 80