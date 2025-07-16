# Gunakan image PHP + Apache
FROM php:8.2-apache

# Install dependensi sistem + ekstensi PHP
RUN apt-get update && apt-get install -y \
    libzip-dev unzip curl git libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Salin semua file ke container
COPY . /var/www/html

# Atur working dir dan permission
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

# Salin konfigurasi vhost agar support .htaccess
COPY ./docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# Install dependensi Laravel (setelah semua ekstensi aktif)
RUN composer install --no-dev --optimize-autoloader

# Jalankan artisan command (opsional)
RUN php artisan config:clear && php artisan route:clear && php artisan view:clear
