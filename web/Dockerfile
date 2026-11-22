FROM docker.io/library/php:8.3-apache

# Install dependensi sistem dan ekstensi PHP yang dibutuhkan Day 1 & Day 2 (Laravel)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-install -j$(nproc) mysqli pdo_mysql opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Copy Composer dari image resmi (tidak butuh Composer di host)
COPY --from=docker.io/library/composer:2 /usr/bin/composer /usr/bin/composer

# Install Laravel 12 (stabil di PHP 8.3) sekali saat build (100% offline saat acara)
WORKDIR /opt
RUN composer create-project laravel/laravel:^12.0 /opt/laravel-base --no-interaction --prefer-dist --no-progress \
    && cd /opt/laravel-base \
    && rm -f app/Http/Controllers/PengeluaranController.php \
    && sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env \
    && sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' .env \
    && sed -i 's/^CACHE_STORE=.*/CACHE_STORE=file/' .env \
    && sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env \
    && sed -i 's/^# *DB_HOST=.*/DB_HOST=db/' .env \
    && sed -i 's/^# *DB_PORT=.*/DB_PORT=3306/' .env \
    && php artisan key:generate --force \
    && chown -R www-data:www-data /opt/laravel-base/storage /opt/laravel-base/bootstrap/cache \
    && chmod -R 775 /opt/laravel-base/storage /opt/laravel-base/bootstrap/cache

# Salin overlay Laravel khusus workshop (layout Blade, routing dinamis, error handler ramah)
# Catatan: Pengeluaran.php (Model) & PengeluaranController.php dimuat per peserta!
COPY laravel-overlay/bootstrap/app.php /opt/laravel-base/bootstrap/app.php
COPY laravel-overlay/config/view.php /opt/laravel-base/config/view.php
COPY laravel-overlay/resources/views/layouts/app.blade.php /opt/laravel-base/resources/views/layouts/app.blade.php
COPY laravel-overlay/resources/views/partials/form.blade.php /opt/laravel-base/resources/views/partials/form.blade.php
RUN rm -f /opt/laravel-base/app/Models/Pengeluaran.php

# Konfigurasi Apache VirtualHost & PHP (OPcache + keamanan)
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY php-custom.ini /usr/local/etc/php/conf.d/99-workshop.ini

# Salin aplikasi web, template peserta, dan skrip CLI
COPY public/ /var/www/html/
COPY templates/ /var/www/templates/
COPY cli/ /var/www/cli/

# Direktori data peserta (akan di-mount ke named volume)
RUN mkdir -p /var/www/participants \
    && chown -R www-data:www-data /var/www/html /var/www/templates /var/www/cli /var/www/participants

WORKDIR /var/www/html
EXPOSE 80
