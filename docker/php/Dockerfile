FROM composer:2 AS composer

FROM php:8.4-fpm-bookworm AS php-base

RUN apt-get update \
    && apt-get install --no-install-recommends --yes \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libxml2-dev \
        libonig-dev \
        libpng-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        dom \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        simplexml \
        xml \
        xmlreader \
        xmlwriter \
        zip \
    && pecl install redis-6.3.0 \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY docker/php/app-init.sh /usr/local/bin/formforge-app-init

RUN chmod 0755 /usr/local/bin/formforge-app-init

WORKDIR /var/www/html

FROM php-base AS development

ARG APP_UID=1000
ARG APP_GID=1000

ENV APP_ENV=local

RUN groupmod --gid "${APP_GID}" www-data \
    && usermod --uid "${APP_UID}" --gid "${APP_GID}" www-data

USER www-data

FROM development AS app-init

USER root

ENTRYPOINT ["formforge-app-init"]

FROM php-base AS production

ENV APP_ENV=production

COPY src/composer.json src/composer.lock ./

RUN composer install \
    --classmap-authoritative \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist

COPY src ./

RUN composer dump-autoload \
    --classmap-authoritative \
    --no-dev \
    --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache

FROM production AS horizon

USER www-data

CMD ["php", "artisan", "horizon"]
