FROM php:8.3-fpm

# Install system dependencies & PHP extensions untuk Laravel 12
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer terbaru
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Node.js 22 LTS (kompatibel penuh dengan Vite 6 & Vue 3)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Konfigurasi upload limit PHP untuk file dokumen
RUN echo "upload_max_filesize = 50M\npost_max_size = 50M\nmemory_limit = 256M" > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html