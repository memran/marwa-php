# MarwaPHP Starter

[![Composer](https://img.shields.io/packagist/v/memran/marwa-php?label=composer)](https://packagist.org/packages/memran/marwa-php)
[![Downloads](https://img.shields.io/packagist/dt/memran/marwa-php?label=downloads)](https://packagist.org/packages/memran/marwa-php)
[![PHP](https://img.shields.io/packagist/dependency-v/memran/marwa-php/php?label=php)](https://packagist.org/packages/memran/marwa-php)
[![PHPUnit](https://img.shields.io/github/actions/workflow/status/memran/marwa-php/ci.yml?branch=main&label=phpunit)](https://github.com/memran/marwa-php/actions/workflows/ci.yml)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/memran/marwa-php/ci.yml?branch=main&label=phpstan)](https://github.com/memran/marwa-php/actions/workflows/ci.yml)
[![GitHub Pages](https://img.shields.io/github/actions/workflow/status/memran/marwa-php/pages.yml?branch=main&label=pages)](https://github.com/memran/marwa-php/actions/workflows/pages.yml)

MarwaPHP Starter is a `composer create-project` application built on top of `memran/marwa-framework`.
It is a thin, production-ready starting point for building real Marwa apps without inheriting framework internals into the app layer.

## Who Should Use It

- developers starting a new Marwa application
- teams that want a clean starter with theme support and route examples
- anyone who wants framework-native defaults without a lot of scaffolding noise

## Install

```bash
composer create-project memran/marwa-php my-app
cd my-app
php -S localhost:8000 -t public/
```

The post-create script generates `.env` from `.env.example` and builds assets when Node.js is available.

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js 20+ for Tailwind development and production builds
- Optional: Docker and Docker Compose

## Docker Compose

The repository includes two Docker Compose variants:

- `docker/docker-compose.yml` for PHP-FPM + Nginx + MariaDB
- `docker/docker-compose.fpm.yml` for PHP-FPM + Caddy + MariaDB

Both compose files boot a MariaDB service that creates the `marwa` database and `marwa` user automatically on first start. The app container is prewired for the Docker host name `mariadb` and uses `docker/docker.env` as its container-local `.env`.

The app runtime reads the standard `DB_*` values:

- `DB_CONNECTION=mysql`
- `DB_HOST=mariadb`
- `DB_PORT=3306`
- `DB_NAME=marwa`
- `DB_USER=marwa`
- `DB_PASSWORD=super-secret`

The MariaDB container still uses `MARIADB_*` values for its first-boot initialization.

Create that file from [docker/docker.env.example](docker/docker.env.example) before starting the stack. The example file contains placeholder credentials only.

```bash
cp docker/docker.env.example docker/docker.env
```

Run the default stack with:

```bash
docker compose -f docker/docker-compose.yml up --build
```

If you change the MariaDB credentials after the first boot, reset the database volume so the container can initialize with the new values:

```bash
docker compose -f docker/docker-compose.yml down -v
```

## Quick Start

```bash
composer install
cp .env.example .env
php -S localhost:8000 -t public/
```

For local frontend assets:

```bash
npm install
npm run dev
```

## Running the App

- `php -S localhost:8000 -t public/` runs the HTTP app locally
- `composer test` runs the PHPUnit suite
- `composer analyse` runs PHPStan
- `composer lint` checks PHP syntax
- `composer ci` runs the local validation chain
- `npm run build` compiles production CSS into `public/assets/css/app.css`
