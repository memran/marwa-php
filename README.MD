# MarwaPHP Framework

MarwaPHP is a lightweight, Laravel-inspired PHP micro-framework built with modern PHP standards and a modular architecture. It provides fast HTTP routing, flexible configuration, powerful CLI tools, and support for events, queues, database migrations, and Twig templating. This project skeleton helps you start new MarwaPHP applications with ease.

---

## Features

- PSR-compliant routing, logging, HTTP messages
- Fast routing via `league/route`
- Configurable service container (PSR-11, League Container)
- Command-line interface with Symfony Console
- Event dispatcher and listeners (PSR-14)
- Database migrations and seeders
- Built-in queuing and task scheduler
- View engine using Twig templates
- Swoole-based HTTP server support for high performance APIs
- Modular folder structure inspired by Laravel

---

## Requirements

- PHP 7.1 or newer
- Composer
- PHP extensions: `curl`, `mbstring`, `pdo`, `json`, `openssl`, `session`, `gd`

---

## Installation

```bash
git clone https://github.com/memran/marwa-php.git my-app
cd my-app
composer install
cp .env.example .env
php marwa migrate:init
php marwa migrate
```

---

## Directory Structure

```
app/            # Controllers, Models, Middlewares, Services
config/         # Configuration files
routes/         # web.php, api.php
resources/      # Views (Twig templates)
database/       # Migrations and seeders
public/         # Entry point (index.php) and assets
storage/        # Logs, cache, sessions
```

---

## Run the Server

You can use the built-in Swoole server (if installed):

```bash
php marwa http:serve
```

Or configure Apache/Nginx to point to `public/index.php`.

---

## Documentation

Full documentation is available in the `docs/` folder and soon at [GitHub Pages or your project website].

---

## License

MIT © Mohammad Emran
