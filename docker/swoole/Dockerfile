FROM phpswoole/swoole:5.1-php8.3-alpine

# Install extra extensions if needed
RUN apk add --no-cache \
        git \
        unzip \
        libzip-dev \
        libpng-dev \
        icu-dev \
    && docker-php-ext-install \
        zip \
        intl \
        pdo \
        pdo_mysql \
    && rm -rf /var/cache/apk/*

WORKDIR /var/www/html

# Optional: copy composer, or rely on host for dev
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

EXPOSE 9501

# Start your Swoole HTTP server
CMD ["php", "bin/swoole-server"]
