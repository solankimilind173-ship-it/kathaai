# Build frontend assets
FROM node:20-alpine AS frontend
WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# Application image
FROM php:8.4-fpm-alpine AS app
WORKDIR /var/www/html

# Install system deps and PHP extensions
RUN apk add --no-cache \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    icu-dev \
    linux-headers \
    postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    bcmath \
    pdo_mysql \
    pdo_pgsql \
    zip \
    exif \
    gd \
    intl \
    opcache \
    pcntl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy app (excluding dev files)
COPY . .
COPY --from=frontend /app/public/build ./public/build

# Install PHP deps (no dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Entrypoint: copy public assets to shared volume for nginx, then run php-fpm
COPY docker/app/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
