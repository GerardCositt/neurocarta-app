# Publicación con Docker (VPS, nube, demo online).
# PHP 8.2 + Apache; front compilado en capa Node.

FROM node:18-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY webpack.mix.js tailwind.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run production

FROM php:8.4-apache-bookworm

RUN a2enmod rewrite headers \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev libzip-dev libonig-dev libpq-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql pdo_pgsql pgsql gd zip opcache intl \
    && rm -rf /var/lib/apt/lists/*

RUN echo "log_errors = On\nerror_log = /dev/stderr\ndisplay_errors = Off" > /usr/local/etc/php/conf.d/errors.ini \
 && echo "upload_max_filesize = 15M\npost_max_size = 20M\nmax_execution_time = 120" > /usr/local/etc/php/conf.d/uploads.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

COPY --from=frontend /app/public/css ./public/css
COPY --from=frontend /app/public/js ./public/js
COPY --from=frontend /app/public/mix-manifest.json ./public/mix-manifest.json

RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data bootstrap/cache \
    && composer install --no-dev --optimize-autoloader --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Filament 3 serves assets as static files under public/css|js/filament/
RUN mkdir -p \
        public/css/filament/forms \
        public/css/filament/support \
        public/css/filament/filament \
        public/js/filament/filament \
        public/js/filament/support \
        public/js/filament/notifications \
        public/js/filament/tables/components \
    && cp vendor/filament/forms/dist/index.css      public/css/filament/forms/forms.css \
    && cp vendor/filament/support/dist/index.css    public/css/filament/support/support.css \
    && cp vendor/filament/filament/dist/theme.css   public/css/filament/filament/app.css \
    && cp vendor/filament/filament/dist/index.js    public/js/filament/filament/app.js \
    && cp vendor/filament/filament/dist/echo.js     public/js/filament/filament/echo.js \
    && cp vendor/filament/support/dist/index.js     public/js/filament/support/support.js \
    && cp vendor/filament/notifications/dist/index.js public/js/filament/notifications/notifications.js \
    && cp vendor/filament/tables/dist/components/table.js public/js/filament/tables/components/table.js

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
