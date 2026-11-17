FROM php:8.2-apache

# Install system dependencies and required PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libonig-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    gd \
    mbstring \
    zip \
    opcache \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Recommended PHP production settings
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'upload_max_filesize=64M'; \
    echo 'post_max_size=64M'; \
    echo 'memory_limit=256M'; \
    echo 'date.timezone=UTC'; \
} > /usr/local/etc/php/conf.d/purifycms.ini

# Set working directory
WORKDIR /var/www/html

# Copy application sources
COPY . /var/www/html/

# Set proper permissions for Apache user
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/inc /var/www/html/tmp

EXPOSE 80

CMD ["apache2-foreground"]
