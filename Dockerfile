FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./
COPY vite.config.js ./

RUN npm ci

COPY resources ./resources
COPY public ./public

RUN npm run build


FROM dunglas/frankenphp:php8.3

RUN setcap -r /usr/local/bin/frankenphp

WORKDIR /app

RUN install-php-extensions \
    pdo_pgsql \
    bcmath \
    opcache \
    zip

RUN apt-get update \
    && apt-get install -y unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

COPY . .

COPY --from=frontend /app/public/build ./public/build

RUN composer dump-autoload \
    --no-dev \
    --optimize \
    --no-interaction \
    --no-scripts

RUN php artisan package:discover --ansi

RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

RUN chown -R www-data:www-data storage bootstrap/cache

COPY Caddyfile /etc/frankenphp/Caddyfile

ENV PORT=8080

EXPOSE 8080

ENTRYPOINT ["frankenphp"]
CMD ["run", "--config", "/etc/frankenphp/Caddyfile"]
