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

Create that file from [docker/docker.env.example](/Users/memran/projects/php-projects/marwa-php/docker/docker.env.example) before starting the stack. The example file contains placeholder credentials only.

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

## Project Structure

- `app/Http/Controllers/` thin application controllers
- `app/Http/Middleware/` starter-specific middleware, such as admin theme switching
- `config/` starter overrides for app identity, middleware, theme names, and error pages
- `routes/` HTTP route definitions
- `resources/views/themes/default/` frontend theme layout and views
- `resources/views/themes/admin/` admin theme layout and views with an automatic light/dark palette
- `resources/views/themes/default/views/maintenance.twig` default maintenance page
- `resources/views/themes/default/views/errors/404.twig` default 404 page
- `resources/views/components/` shared Twig partials used by the frontend theme
- `modules/` optional feature modules, including thin MVP shells for Auth, Users, Activity, Notifications, Settings, and DashboardStatus
- `database/seeders/` starter seeders, including the first admin account used by the auth module
- `tests/` app-specific PHPUnit coverage

## How This Starter Uses Marwa

Marwa Framework owns the HTTP kernel, container, config loading, router, view engine, middleware pipeline, console kernel, cache handling, and helper functions.

This starter only adds:

- route definitions in `routes/web.php` and `routes/api.php`
- thin controllers that call the framework controller `view()` helper
- one app middleware that selects the admin theme for the admin route group
- theme views and shared Twig partials
- bundled module shells that demonstrate Marwa's module discovery and route loading
- starter-focused tests that exercise real application behavior

## Configuration

The starter keeps configuration narrow:

- `config/app.php` sets the application name, key, providers, middleware stack, and debugbar collectors
- `config/database.php` enables SQLite by default so the Auth and Users modules work on a fresh starter install
- `config/event.php` wires the module migration bootstrap listener
- `config/console.php` discovers app commands from `app/Commands`
- `config/view.php` defines the frontend and admin theme names plus view cache defaults
- the rest of the runtime defaults come from Marwa Framework

Common environment values:

- `APP_NAME`
- `APP_KEY`
- `APP_DEBUG`
- `DB_ENABLED`
- `DB_CONNECTION`
- `DB_DATABASE`
- `ADMIN_BOOTSTRAP_EMAIL`
- `ADMIN_BOOTSTRAP_PASSWORD`
- `FRONTEND_THEME`
- `ADMIN_THEME`

See [config/README.md](config/README.md) for the current config map.

## Routing, Controllers, and Views

`routes/web.php`:

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\AdminThemeMiddleware;
use Marwa\Framework\Facades\Router;

Router::get('/', [HomeController::class, 'index'])->name('home')->register();

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class]], static function ($routes): void {
    $routes->get('/', [DashboardController::class, 'index'])->name('admin.dashboard')->register();
});
```

`app/Http/Controllers/HomeController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;

final class HomeController extends Controller
{
    public function index(): ResponseInterface
    {
        return $this->view('home/index');
    }
}
```

`app/Http/Middleware/AdminThemeMiddleware.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Marwa\Framework\Views\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AdminThemeMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var View $view */
        $view = app(View::class);
        $previousTheme = $view->theme();
        $adminTheme = trim((string) config('view.adminTheme', 'admin')) ?: 'admin';

        $view->theme($adminTheme);

        try {
            return $handler->handle($request);
        } finally {
            $view->theme($previousTheme);
        }
    }
}
```

Frontend theme view example:

```twig
{% extends "layout.twig" %}

{% block title %}Home{% endblock %}
{% block page_title %}Dashboard{% endblock %}
{% block content %}
<section>Starter content for the frontend theme.</section>
{% endblock %}
```

Maintenance and 404 templates are kept in the default theme tree:

- `resources/views/themes/default/views/maintenance.twig`
- `resources/views/themes/default/views/errors/404.twig`

The framework uses those templates when the matching `config/app.php` entries are set:

- `app.maintenance.template`
- `app.error404.template`

## Running the App

- `php -S localhost:8000 -t public/` runs the HTTP app locally
- `composer test` runs the PHPUnit suite
- `composer analyse` runs PHPStan
- `composer lint` checks PHP syntax
- `composer ci` runs the local validation chain
- `npm run build` compiles production CSS into `public/assets/css/app.css`

## Testing

Tests in this repository should cover starter-specific behavior only.

Good coverage examples:

- route behavior in `routes/web.php` and `routes/api.php`
- app middleware behavior
- app-specific config integration
- starter module wiring
- module-backed auth and user flows

## Admin Modules

The starter now ships with DB-backed `Auth` and `Users` modules.

- `Auth` owns login, logout, forgot-password, and reset-password flows.
- `Users` owns the admin user table and CRUD screens.
- Module migrations run automatically when the app boots modules.
- The starter seeds a default admin account on first boot when the users table is empty.

Default bootstrap credentials:

- email: `admin@marwa.test`
- password: `ExampleAdminPassword123!`

Override them with `ADMIN_BOOTSTRAP_EMAIL` and `ADMIN_BOOTSTRAP_PASSWORD` in `.env`.

Do not add tests for framework internals that already belong to `memran/marwa-framework`.

## App Code vs Framework

Put code in the app when it is:

- specific to this starter
- specific to a project built from this starter
- configuration or presentation specific

Put code in the framework when it is:

- reusable across Marwa applications
- core routing, middleware, view, console, config, cache, or helper behavior

## Customization

Most custom work should happen in one of these places:

1. `routes/` for HTTP entry points
2. `app/Http/Controllers/` for request handling
3. `app/Http/Middleware/` for cross-cutting request behavior
4. `resources/views/themes/` for theme-specific rendering
5. `resources/views/themes/default/views/maintenance.twig` and `resources/views/themes/default/views/errors/404.twig` for the starter error pages
6. `config/` for starter-specific defaults
7. `modules/` for bundled feature-module shells and examples
8. `public/themes/admin/assets/css/app.css` for the admin theme token system and light/dark styling

If you find yourself repeating the same workaround in more than one file, that is a strong signal the framework should grow the capability instead.

## Deployment

- set the web server document root to `public/`
- build production assets with `npm run build`
- run `composer ci` before release
- keep `.env` out of version control
- use framework cache commands when you need to warm or clear config and route caches

## Contribution Notes

- keep changes focused and small
- update README and tests when app behavior changes
- avoid adding code that only mirrors framework features
- prefer framework-native APIs over new app-level abstractions

## Framework Suggestions

- `app_path()` helper is missing from the framework helper set. The starter currently has to refer to `base_path('app/...')` when it wants an app directory path.
- A dedicated `view:clear` command would remove the need for starter-side view cache cleanup if that behavior is ever needed.
