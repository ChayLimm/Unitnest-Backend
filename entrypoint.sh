#!/bin/bash
set -e

# Ensure .env exists
if [ ! -f .env ]; then
  cp .env.example .env
fi

# Install composer dependencies if missing
if [ ! -d vendor ]; then
  composer install
fi

# Fix permissions
chown -R www-data:www-data storage bootstrap/cache

# Start Laravel dev server
php artisan serve --host=0.0.0.0 --port=8000
