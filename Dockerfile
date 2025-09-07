FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential libpng-dev libjpeg-dev libfreetype6-dev \
    locales zip jpegoptim optipng pngquant gifsicle \
    vim unzip git curl libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Give permission for mounted code
RUN chown -R www-data:www-data /var/www

# Optional: expose the port Laravel dev server will use
EXPOSE 8000

CMD ["entrypoint.sh"]
