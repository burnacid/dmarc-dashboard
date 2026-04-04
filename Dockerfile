FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

FROM node:22-alpine AS assets
WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm install

COPY resources ./resources
COPY public ./public
COPY vite.config.js tailwind.config.js postcss.config.js ./
RUN npm run build

FROM php:8.4-apache-bookworm

ARG APP_DIR=/var/www/html
WORKDIR ${APP_DIR}

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libonig-dev \
        libzip-dev \
        libxml2-dev \
        libsqlite3-dev \
        sqlite3 \
        unzip \
    && docker-php-ext-install \
        bcmath \
        mbstring \
        pcntl \
        pdo_mysql \
        pdo_sqlite \
        zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=${APP_DIR}/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY . ${APP_DIR}
COPY --from=vendor /app/vendor ${APP_DIR}/vendor
COPY --from=assets /app/public/build ${APP_DIR}/public/build

RUN chown -R www-data:www-data ${APP_DIR}/storage ${APP_DIR}/bootstrap/cache ${APP_DIR}/database

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]

