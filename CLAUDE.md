# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Identity

MarwaPHP Starter — a `composer create-project` application built on `memran/marwa-framework`.
It provides a thin, production-ready starting point with theme support, module system, and admin UI.

## Essential Commands

```bash
# Install dependencies
composer install

# Run migrations
php marwa migrate
php marwa module:migrate
php marwa module:seed

# Start server
php -S localhost:8000 -t public/

# Asset development
npm install && npm run dev        # Dev mode (frontend + admin)
npm run css:dev:admin             # Admin theme CSS only
npm run build                     # Production build

# Docker
docker compose -f docker/docker-compose.yml up --build
cp docker/docker.env.example docker/docker.env  # Before first Docker run

# Quality checks
composer test       # PHPUnit suite
composer analyse    # PHPStan
composer lint       # PHP syntax check
composer ci         # Full validation chain

# Database
php marwa db:check  # Check active DB connection
```

**Admin login:** uses `ADMIN_BOOTSTRAP_EMAIL` / `ADMIN_BOOTSTRAP_PASSWORD` from `.env`.

## Framework Dependency Map

```
marwa-framework   → core runtime, container, HTTP kernel, middleware, router, console
marwa-module      → manifest parsing, module discovery, provider bootstrap
marwa-view        → Twig rendering, namespaced view roots
```

## Boot Flow

1. `public/index.php` creates `Marwa\Framework\Application`
2. Application loads `.env`, registers core services
3. `AppBootstrapper` loads framework defaults + config overrides from `config/`
4. `ModuleBootstrapper` loads module manifests, providers, views, routes
5. `HttpKernel` runs middleware pipeline and route dispatch
6. `ViewAdapter` resolves and renders Twig templates

CLI entrypoint (`marwa`) follows the same bootstrap pattern.

## Architecture (App vs Framework)

**Rule:** Use framework helpers and services first: `env()`, `config()`, `base_path()`, `resources_path()`, `validate_request()`, `view()`, `cache()`.
Use `marwa-support` utilities: `Arr`, `Collection`, `Str`, `Json`, `Hash`, `Security`, `Sanitizer`, `Validator`, `XSS`.

Put code in the starter only when it is app-specific or starter-specific.
Do not recreate framework features in app space.

## Project Layout (Key Directories)

| Path | Purpose |
|------|---------|
| `app/Http/Controllers/` | Thin app controllers |
| `app/Http/Middleware/` | App-specific middleware |
| `app/Commands/` | Starter console commands |
| `config/` | Starter config overrides |
| `routes/` | HTTP entry points |
| `resources/views/` | Twig layouts, themes, shared partials |
| `resources/views/themes/` | Theme-aware templates |
| `modules/` | Self-contained modules |
| `tests/` | App-specific PHPUnit tests |
| `public/` | Entry points and compiled assets |

## Module Conventions

Self-contained under `modules/<Name>/`:
```
manifest.php                    # name, slug, version, providers, routes, migrations
{Name}ServiceProvider.php    # register() + boot(View) methods
routes/http.php
Http/Controllers/
Models/
resources/views/               # Twig templates
database/migrations/
database/seeders/
```

**Key rules:**
- Register view namespaces in `boot()` via `$app->view()->addNamespace('slug', __DIR__ . '/resources/views')`
- Prefer manifest `paths.views` for module Twig namespaces
- Keep migrations listed in manifest `migrations` array
- Use direct `ActivityRecorder` calls for activity logging
- CSRF and auth follow existing starter patterns
- Declare dependencies in manifest metadata, not runtime glue

Use `modules/Users/` as the reference shape for new modules.

## Active Modules

| Module | Path | Description |
|--------|------|-------------|
| Admin | `admin/` | Admin panel layout & shared components |
| Auth | `modules/Auth/` | Authentication, login, logout |
| Users | `modules/Users/` | User CRUD (soft-delete, email uniqueness) |
| Roles | `modules/Roles/` | Role-based permissions |
| Dashboard | `modules/Dashboard/` | Admin dashboard with widget system |
| Activity | `modules/Activity/` | Admin activity log viewer |
| Notifications | `modules/Notifications/` | System notifications |
| Settings | `modules/Settings/` | Database-backed settings with caching |
| DatabaseManager | `modules/DatabaseManager/` | Raw SQL console (admin-only) |

Keep DatabaseManager disabled in production/staging; enable with `DATABASE_MANAGER_ENABLED=1`.

## View & Theme System

- Theme selection from config/middleware, NOT from session state
- Active/fallback theme via `config/view.php`: `activeTheme`, `fallbackTheme`, `adminTheme`
- Theme templates: `resources/views/themes/{theme_name}/`
- Module templates: `modules/<Name>/resources/views/` under registered namespace
- View extensions registered in `config/view.php` (`extensions` array)
- Admin theme uses Tailwind utilities + Alpine + Lucide sprite icons

## Database

- Default connection is SQLite via `config/database.php`
- Docker Compose provides MariaDB alternative with `docker/docker.env`
- Migrations: `database/migrations/` (app-level) + module `database/migrations/`
- Use framework model/migration/seeder conventions
- Run `php marwa module:seed` for module-local seeders

## Code Style

- `declare(strict_types=1);`
- PSR-1, PSR-12, PSR-4
- 4-space indentation, typed properties, explicit return types
- PascalCase for classes, `*Test.php` for test files
- Max ~200 lines per class, ~20 lines per method

## Testing

- Run `composer test` then `composer analyse`
- Test starter behavior only: routes, middleware, commands, module wiring
- Do NOT test framework internals
- Feature-level tests using real requests/responses preferred

## Documentation

- `README.MD` — user-facing setup and usage
- `AGENTS.md` — comprehensive repository guidelines
- `ARCHITECTURE.md` — layer map and framework boundary details
- `docs/module-authoring.md` — guide for creating new modules