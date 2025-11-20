FROM php:8.2-fpm-alpine

# Install system deps and PHP extensions
RUN apk add --no-cache \
        git \
        unzip \
        libzip-dev \
        libpng-dev \
        icu-dev \
        oniguruma-dev \
        bash \
    && docker-php-ext-install \
        zip \
        intl \
        pdo \
        pdo_mysql \
        opcache \
    && rm -rf /var/cache/apk/*

WORKDIR /var/www/html

# Optional: install Composer inside container (useful for CI/build)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# You can add custom ini files here later, e.g.:
# COPY docker/php-fpm/conf.d/*.ini /usr/local/etc/php/conf.d/

EXPOSE 9000

CMD ["php-fpm"]
