# Build frontend assets in a dedicated stage
FROM node:18-alpine AS node_builder
WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

# Install PHP dependencies in a dedicated stage
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-progress --no-scripts --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize

# Final runtime image: PHP-FPM + Nginx
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    nginx \
    bash \
    icu-dev \
    libxml2-dev \
    oniguruma-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    zlib-dev \
    libzip-dev \
    curl

RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
 && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache

RUN mkdir -p /run/nginx

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY --from=node_builder /app/dist /var/www/html/dist
COPY .env.docker /var/www/html/.env
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/dist

EXPOSE 80

CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
