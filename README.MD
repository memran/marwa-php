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
php marwa migrate
php marwa module:migrate
php marwa module:seed
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

Admin login uses the bootstrap credentials from `.env`:

- `ADMIN_BOOTSTRAP_EMAIL`
- `ADMIN_BOOTSTRAP_PASSWORD`

That login is session-backed and will use the seeded admin user record when the database is available, while still falling back safely during early bootstrap.
CSRF protection is enabled by default for unsafe HTTP methods, and the starter Auth and Users forms submit framework-managed CSRF tokens.
The starter admin seeder now lives in `modules/Users/database/seeders/AdminUserSeeder.php` and should be run through `php marwa module:seed` during setup.
The admin theme now uses Tailwind utilities directly in the Twig templates, the framework Alpine bridge for its mobile sidebar toggle, and a Lucide sprite-backed icon partial in the sidebar and user status badges.
The admin sidebar includes Users and Activity sections that link to the module CRUD screens and activity feed under `/admin/users` and `/admin/activity`.
The admin sidebar also exposes a raw SQL database console at `/admin/database` for admin users only when `DATABASE_MANAGER_ENABLED=1` or the app is running outside `production`/`staging`. It runs single-statement raw SQL directly against the configured connection, paginates result sets in the UI, shows query errors clearly, and requires explicit confirmation before destructive queries such as `UPDATE` or `DELETE`.
The starter also ships an admin-only Settings module at `/admin/settings`. It stores predefined `category -> key -> value` records in the database, caches them for fast reads, reloads them at bootstrap, and mirrors selected values into runtime config so they are globally readable through `config('settings.category.key')` and relevant core config keys.
Soft-deleted users stay visible in the Users module with a `Trashed` badge and a restore action.
User creation now rejects duplicate emails, and delete actions ask for a browser confirmation before submitting.
The Activity module records admin login, logout, and user CRUD events through direct `ActivityRecorder` calls in the starter workflows, then shows them in the dashboard feed and on the activity page.
To run module-local seeders manually, use `php marwa module:seed`.
If you want to add a new module using the same conventions as `Users`, see [docs/module-authoring.md](docs/module-authoring.md).

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
php marwa migrate
php marwa module:migrate
php marwa module:seed
php -S localhost:8000 -t public/
```

For local frontend assets:

```bash
npm install
npm run dev
```

To rebuild the admin theme assets while developing:

```bash
npm run css:dev:admin
```

## Running the App

- `php -S localhost:8000 -t public/` runs the HTTP app locally
- `composer test` runs the PHPUnit suite
- `composer analyse` runs PHPStan
- `composer lint` checks PHP syntax
- `composer ci` runs the local validation chain
- `php marwa db:check` checks the active database connection and prints the result
- `npm run build` compiles production CSS into `public/assets/css/app.css` and `public/themes/admin/assets/css/app.css`
- `npm run css:build:admin` compiles only the admin theme stylesheet into `public/themes/admin/assets/css/app.css`
- The admin layouts load `public/themes/admin/css/app.css`, which forwards to the compiled admin stylesheet above
