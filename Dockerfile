FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim \
    optipng \
    pngquant \
    gifsicle \
    vim \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    libpq-dev   \
    libzip-dev \  
    libicu-dev \ 
    && apt-get clean && rm -rf /var/lib/apt/lists/*


# Install PHP extensions (ADD THIS LINE)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd pdo_pgsql pgsql zip intl opcache      


# Clean up (ADD THIS LINE TOO)
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# this basically copy composer.json and install requirements

# Set working directory
WORKDIR /var/www

# Copy only composer files first for better caching
COPY composer.json composer.lock* ./

# Install dependencies (separate step for caching)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copy the rest of the application
COPY . .

# Run composer scripts after copying all files
# RUN composer run-script post-install-cmd

# Fix permissions (important for Laravel)
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Copy entrypoint script
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose Laravel dev server port
EXPOSE 8000

# Use entrypoint to handle dependencies and permissions
CMD ["entrypoint.sh"]