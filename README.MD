# MarwaPHP Starter

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

Create that file from [docker/docker.env.example](docker/docker.env.example) before starting the stack. The example file contains placeholder credentials only.

```bash
cp docker/docker.env.example docker/docker.env
```

Run the default stack with:

```bash
docker compose -f docker/docker-compose.yml up --build
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

## Documentation

Use the docs tree for the full starter guide:

- [docs/index.md](docs/index.md) for the table of contents
- [docs/getting-started.md](docs/getting-started.md) for install and first run
- [docs/project-structure.md](docs/project-structure.md) for where app code belongs
- [docs/architecture.md](docs/architecture.md) for the app/framework boundary
- [docs/configuration.md](docs/configuration.md) for starter config and env values
- [docs/routing-controllers-views.md](docs/routing-controllers-views.md) for routes and theme rendering
- [docs/modules.md](docs/modules.md) for the bundled Auth and Users modules
- [docs/testing.md](docs/testing.md) for test scope and commands
- [docs/deployment.md](docs/deployment.md) for runtime and release notes
- [docs/contributing.md](docs/contributing.md) for contribution rules

## Running the App

- `php -S localhost:8000 -t public/` runs the HTTP app locally
- `composer test` runs the PHPUnit suite
- `composer analyse` runs PHPStan
- `composer lint` checks PHP syntax
- `composer ci` runs the local validation chain
- `npm run build` compiles production CSS into `public/assets/css/app.css`
