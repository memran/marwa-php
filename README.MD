# MarwaPHP Framework

![CI](https://github.com/memran/marwa-php/actions/workflows/ci.yml/badge.svg)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-8892BF.svg)
![License](https://img.shields.io/badge/License-MIT-2ea44f.svg)
![Tests](https://img.shields.io/badge/Tests-PHPUnit%20passing-success.svg)
![Analysis](https://img.shields.io/badge/Static%20Analysis-PHPStan%20ready-blue.svg)
![Downloads](https://img.shields.io/packagist/dm/memran/marwa-php)
![Total Downloads](https://img.shields.io/packagist/dt/memran/marwa-php)

MarwaPHP is a polished PHP framework starter for teams that want a fast launch without sacrificing structure. It combines a clean MVC layout, Twig views, PSR-friendly foundations, and a production-minded toolchain so you can move from idea to deployment with less friction.

See the repo architecture guide in [ARCHITECTURE.md](ARCHITECTURE.md) for the framework-first split between core, modules, and app code.

It is a strong fit for:

- product teams building APIs and web apps
- developers who want a lightweight starter
- projects that need modular architecture, fast routing, and simple deployment

The UI is built with Tailwind CSS. Use `npm run dev` while developing and `npm run build` before shipping assets.
On fresh installs, the post-create bootstrap will try to run `npm ci` and `npm run build` automatically when Node.js is available.

## Why MarwaPHP

- Launch-ready scaffold with a clean developer experience
- Twig rendering with reusable layouts
- Strong defaults for routing, error pages, and CSRF-safe forms
- Composer scripts, PHPUnit, PHPStan, and CI already wired in
- Docker support for both Nginx and Caddy local stacks

## Features

- Lightweight MVC application structure
- Twig views with a polished default landing page
- PSR-7, PSR-11, PSR-15, and PSR-16 friendly building blocks
- HTTP routing with middleware support
- Module scaffolding support for custom app modules
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

The document root is `public/`. Main application routes live in `routes/web.php`.

## Configuration

The post-create script generates `.env` automatically from `.env.example`. Review it and adjust the values for your environment.

- The sample env file already includes the framework defaults for app name, URL, environment, debug mode, timezone, and module loading.
- Runtime cache paths and other framework defaults are managed by the config files under `config/`.
- Optional integrations: database and cache variables can be added later when needed
- See [config/README.md](config/README.md) for a quick index of the base framework config files.

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
- `docker compose -f docker/docker-compose.yml up -d` starts the Nginx + PHP-FPM stack
- `docker compose -f docker/docker-compose.fpm.yml up -d` starts the Caddy + PHP-FPM stack
- `npm ci --no-audit --no-fund` installs the Tailwind toolchain from the committed lockfile

## Project Layout

- `app/` controllers and small support services
- `config/` framework and runtime configuration
- `routes/` HTTP route definitions
- `modules/` optional application modules
- `resources/views/` Twig views and shared partials
- `public/` document root and compiled assets
- `database/` migrations and seeders
- `tests/` unit and feature tests
- `docker/` Nginx, Caddy, PHP-FPM, and optional Tailwind asset containers

## Production Notes

- Keep `.env` out of version control
- Run the test and analysis scripts before each release
- Use the Docker stack or your own PHP-FPM web server with `public/` as the document root
- Add database or cache services only when the application layer needs them

## Contributing

Keep changes focused and easy to review. Add tests for behavior changes, and update this README whenever commands, dependencies, or runtime behavior change.

For the framework-first architecture rules and contribution checklist, see [ARCHITECTURE.md](ARCHITECTURE.md) and [CONTRIBUTING.MD](CONTRIBUTING.MD).
