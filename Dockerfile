FROM php:8.3-cli

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

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

RUN mkdir -p \
    writable/cache \
    writable/logs \
    writable/session \
    writable/uploads \
    && chmod -R 775 writable

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t public router.php"]