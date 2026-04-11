---
layout: default
title: Getting Started
---

# Getting Started

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

## Quick Start

```bash
composer install
cp .env.example .env
php -S localhost:8000 -t public/
```

For frontend assets:

```bash
npm install
npm run dev
```

## Docker Compose

- `docker/docker-compose.yml` runs PHP-FPM + Nginx + MariaDB
- `docker/docker-compose.fpm.yml` runs PHP-FPM + Caddy + MariaDB

Copy `docker/docker.env.example` to `docker/docker.env` before starting either stack.

```bash
cp docker/docker.env.example docker/docker.env
docker compose -f docker/docker-compose.yml up --build
```
