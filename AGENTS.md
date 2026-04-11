# Repository Guidelines

## Identity
- This repository is a Marwa Framework starter application created with `composer create-project`.
- The framework is the dependency at `vendor/memran/marwa-framework/` and is the source of truth for APIs, lifecycle, and conventions.
- Keep the app minimal, production-ready, and easy for end users to extend.

## Marwa Rules
- Use Marwa helpers and services first: `env()`, `base_path()`, `cache_path()`, `config()`, `public_path()`, `resources_path()`, `storage_path()`, and the framework `view()`/router/controller APIs.
- Do not recreate framework features in app space.
- Prefer route files, controllers, middleware, config, and Twig views that follow framework conventions.
- Theme selection must come from configuration or request middleware, not from session state.
- If the framework is missing a capability that would remove starter boilerplate, surface it as a framework suggestion instead of hiding it with app glue.

## App vs Framework
- Put code in the starter only when it is app-specific, starter-specific, or configuration-specific.
- If a behavior would be reused by other Marwa apps, it usually belongs in the framework.
- Keep cross-cutting behavior in the framework or in a single app middleware/service, not duplicated in multiple controllers or views.
- Do not add wrapper classes around framework services unless they add real app value.
- If a helper like `app_path()` is needed but not provided by the framework, note it as a framework gap rather than inventing a larger local abstraction.

## Project Layout
- `app/Http/Controllers/` contains thin app controllers.
- `app/Http/Middleware/` contains app-specific middleware, such as theme switching.
- `app/Commands/` contains starter console commands, such as the database connectivity check.
- `config/` contains only starter overrides; avoid restating framework defaults.
- `config/database.php` enables SQLite by default so the Auth and Users modules are usable on a fresh starter install.
- The Docker Compose files also include a MariaDB service for local container-based development, with the app container pointed at that database host.
- Docker stack credentials are copied from `docker/docker.env.example` into an ignored `docker/docker.env` runtime file mounted into the app container.
- `routes/` defines the HTTP entry points.
- `resources/views/` contains Twig layouts, theme views, and shared partials.
- Starter maintenance and 404 pages live under `resources/views/themes/default/views/` so the framework can resolve them through `config/app.php`.
- `modules/` stays optional and self-contained.
- Module migrations are bootstrapped from a single app listener. Admin login is session-backed and uses `ADMIN_BOOTSTRAP_EMAIL` / `ADMIN_BOOTSTRAP_PASSWORD` from `.env`; the starter still seeds an admin account from `modules/Users/database/seeders/AdminUserSeeder.php` for the users module when the users table is empty.
- `tests/` contains only app-specific PHPUnit coverage.

## Testing Scope
- Test starter behavior only: routes, custom middleware, starter commands if any, config integration, and custom module wiring.
- Do not add tests for framework internals, framework commands, or framework view/theme mechanics that are already covered in `vendor/memran/marwa-framework/`.
- Prefer behavior-level assertions that exercise real requests and responses.

## Documentation
- Keep `README.md` synchronized with the actual starter behavior.
- Document `composer create-project`, quick start, project structure, routing, controller, view usage, and the split between app code and framework code.
- If the starter changes, update the README in the same change.

## Refactoring
- Remove redundant app files when Marwa already provides the same behavior.
- Keep templates and controllers small and explicit.
- Avoid dead code, placeholder abstractions, and duplicated configuration.
- If a starter file is only compensating for a framework shortcoming, keep the workaround minimal and document the limitation.
- Keep the starter error-page templates aligned with `config/app.php` and the active theme tree.

## Definition Of Done
- The starter is still thin after the change.
- App-specific tests pass and framework tests are not duplicated here.
- README and AGENTS reflect the actual repository behavior.
- Any framework-level friction is called out clearly in the final response.
