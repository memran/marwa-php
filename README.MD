# MarwaPHP Framework

![CI](https://github.com/memran/marwa-php/actions/workflows/ci.yml/badge.svg)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-8892BF.svg)
![License](https://img.shields.io/badge/License-MIT-2ea44f.svg)
![Tests](https://img.shields.io/badge/Tests-PHPUnit%20passing-success.svg)
![Analysis](https://img.shields.io/badge/Static%20Analysis-PHPStan%20ready-blue.svg)

MarwaPHP is a polished PHP framework starter for teams that want a fast launch without sacrificing structure. It combines a clean MVC layout, Twig theming, PSR-friendly foundations, and a production-minded toolchain so you can move from idea to deployment with less friction.

It is a strong fit for:

- product teams building APIs and web apps
- developers who want a lightweight, theme-aware starter
- projects that need modular architecture, fast routing, and simple deployment

## Authentication Module

The starter ships with an optional auth module under `modules/Auth/`. Enable it with `AUTH_MODULE_ENABLED=true` in `.env` and the framework will load the module, register its routes, and expose a complete auth starter:

- login, register, logout, and profile pages
- remember-me support with secure token storage
- password reset and password change flows
- role-based admin starter views
- starter migrations, seeders, policies, and middleware

Seed the initial admin account with:

```bash
php marwa auth:seed
```

The UI is built with Tailwind CSS. Use `npm run dev` while developing and `npm run build` before shipping assets.
On fresh installs, the post-create bootstrap will try to run `npm ci` and `npm run build` automatically when Node.js is available.

## Why MarwaPHP

- Launch-ready scaffold with a clean developer experience
- Theme-aware rendering with reusable Twig layouts
- Strong defaults for routing, error pages, and CSRF-safe forms
- Composer scripts, PHPUnit, PHPStan, and CI already wired in
- Docker support for both Nginx and Caddy local stacks

## Features

- Lightweight MVC application structure
- Theme-aware Twig views with a polished default landing page
- CSRF-protected theme switching form
- PSR-7, PSR-11, PSR-15, and PSR-16 friendly building blocks
- HTTP routing with middleware support
- Optional auth module with seeded starter roles and admin pages
- PHPUnit tests, PHPStan analysis, and PHP-CS-Fixer support
- Nginx and Caddy Docker stacks for local development
- Safe starter defaults for configuration and environment handling

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js 20+ for Tailwind development and asset builds
- Optional: Docker and Docker Compose

## Quick Start

```bash
composer create-project memran/marwa-php my-app
cd my-app
# .env is generated automatically by the post-create script.
# If Node.js is installed, Tailwind assets are built automatically.
php -S localhost:8000 -t public/
```

The document root is `public/`. Main application routes live in `routes/web.php`, and the active theme views live in `resources/views/themes/default/views/`.

## Configuration

The post-create script generates `.env` automatically from `.env.example`. Review it and adjust the values for your environment.

- Core app: `APP_NAME`, `APP_TITLE`, `APP_URL`, `APP_ENV`, `APP_DEBUG`
- Auth module: `AUTH_MODULE_ENABLED`, `AUTH_ADMIN_EMAIL`, `AUTH_ADMIN_PASSWORD`, `AUTH_ADMIN_ROLE`, `AUTH_DEFAULT_ROLE`
- Docker: `NGINX_PORT`
- Optional integrations: database and cache variables can be added later when needed

Never commit secrets or machine-specific values.

## Developer Commands

- `composer test` runs the PHPUnit suite
- `composer analyse` runs PHPStan
- `composer lint` checks PHP syntax across the repository
- `composer fix` runs PHP-CS-Fixer
- `composer ci` runs the local validation chain used by CI
- `npm run dev` watches and rebuilds the Tailwind stylesheet
- `npm run build` generates the production Tailwind stylesheet
- `docker compose -f docker/docker-compose.yml --profile assets up -d assets` runs the Tailwind watcher inside Docker
- `docker compose -f docker/docker-compose.fpm.yml --profile assets up -d assets` runs the Tailwind watcher with the Caddy stack
- `php marwa auth:seed` seeds the starter auth module
- `docker compose -f docker/docker-compose.yml up -d` starts the Nginx + PHP-FPM stack
- `docker compose -f docker/docker-compose.fpm.yml up -d` starts the Caddy + PHP-FPM stack
- `npm ci --no-audit --no-fund` installs the Tailwind toolchain from the committed lockfile

## Project Layout

- `app/` controllers and small support services
- `config/` framework and runtime configuration
- `routes/` HTTP route definitions
- `modules/Auth/` optional auth module with routes, migrations, seeders, and views
- `resources/views/themes/default/views/` Twig views for the active theme
- `resources/views/themes/*/` theme manifests and overrides
- `public/` document root and compiled assets
- `database/` migrations and seeders
- `tests/` unit and feature tests
- `docker/` Nginx, Caddy, PHP-FPM, and optional Tailwind asset containers

## Production Notes

- Set `APP_ENV=production` and `APP_DEBUG=false`
- Keep `.env` out of version control
- Run the test and analysis scripts before each release
- Use the Docker stack or your own PHP-FPM web server with `public/` as the document root
- Add database or cache services only when the application layer needs them

## Contributing

Keep changes focused and easy to review. Add tests for behavior changes, and update this README whenever commands, dependencies, or runtime behavior change.
