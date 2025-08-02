FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev zip libonig-dev \
    libpq-dev libxml2-dev libcurl4-openssl-dev \
    libssl-dev && \
    docker-php-ext-install pdo pdo_mysql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www