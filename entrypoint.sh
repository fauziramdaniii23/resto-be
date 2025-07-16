#!/bin/bash

# Generate oauth keys if not exists
if [ ! -f storage/oauth-private.key ]; then
    echo "Generating Passport keys..."
    php artisan passport:keys --force
    chmod 600 storage/oauth-private.key storage/oauth-public.key
fi

# Set permission
chown -R www-data:www-data storage bootstrap/cache

# Start Apache
apache2-foreground
