FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    unzip git curl libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Salin semua file ke container
COPY . /var/www/html

# Atur working dir dan permission
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

# Install dependensi Laravel
RUN composer install --no-dev --optimize-autoloader

# Jalankan artisan command (opsional)
RUN php artisan config:clear && php artisan route:clear && php artisan view:clear
