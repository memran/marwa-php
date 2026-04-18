# Repository Guidelines

## Identity

- This repository is a Marwa Framework starter application created with `composer create-project`.
- The framework is the dependency at `vendor/memran/marwa-framework/` and is the source of truth for APIs, lifecycle, and conventions.
- Keep the app minimal, production-ready, and easy for end users to extend.

## Marwa Rules

- Use Marwa helpers and services first: `env()`, `base_path()`, `cache_path()`, `config()`, `public_path()`, `resources_path()`, `storage_path()`, and the framework `view()`/router/controller APIs.
- Prefer `marwa-support` utilities over custom helpers when an equivalent already exists, especially `Arr`, `Collection`, `File`, `Hash`, `Json`, `Sanitizer`, `Security`, `Str`, `Url`, `Validation`, `Validator`, and `XSS`.
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
- `resources/views/` contains Twig layouts, theme views, and shared partials. The admin theme uses a shared Lucide sprite-backed icon partial for consistent SVGs, and the Users module shows soft-deleted rows with a restore action, asks for delete confirmation, and rejects duplicate emails at the starter layer. The Activity module records admin login/logout and user CRUD events through direct starter workflow calls and renders them on `/admin/activity` and in the dashboard feed. The admin-only Database Manager module provides a high-risk raw SQL console at `/admin/database` with confirmation for destructive queries. The admin-only Settings module exposes update-only predefined settings at `/admin/settings`, loads them at bootstrap, caches them, and mirrors them into `config('settings.*')`.
- Keep the Database Manager disabled by default in `production` and `staging`; enable it explicitly with `DATABASE_MANAGER_ENABLED=1` only for controlled environments.
- Starter maintenance and 404 pages live under `resources/views/themes/default/views/` so the framework can resolve them through `config/app.php`.
- `modules/` stays optional and self-contained.
- Module migrations and seeders must run through the framework CLI commands, not from HTTP requests. Use `php marwa migrate`, `php marwa module:migrate`, and `php marwa module:seed` during setup. Admin login is session-backed and uses `ADMIN_BOOTSTRAP_EMAIL` / `ADMIN_BOOTSTRAP_PASSWORD` from `.env`; the starter admin seed lives at `modules/Users/database/seeders/AdminUserSeeder.php`, and the admin sidebar exposes the Users CRUD section at `/admin/users` plus the Activity feed at `/admin/activity`.
- `tests/` contains only app-specific PHPUnit coverage.

## Structure Reference

- Framework namespace and source of truth live under `vendor/memran/marwa-framework/`, primarily `src/` with folders such as `Adapters/`, `Bootstrappers/`, `Config/`, `Console/`, `Contracts/`, `Controllers/`, `Database/`, `Exceptions/`, `Facades/`, `Middlewares/`, `Notifications/`, `Providers/`, `Security/`, `Supports/`, `Validation/`, and `Views/`.
- Framework entry points worth checking before changing starter behavior are `vendor/memran/marwa-framework/src/Application.php` and `vendor/memran/marwa-framework/src/HttpKernel.php`.
- Framework helpers are re-exported from `vendor/memran/marwa-framework/src/Supports/Helpers.php`, with modular helper files under `vendor/memran/marwa-framework/src/Supports/Helpers/`.
- Validation logic should follow `Marwa\Support\Validation` and the framework’s adapters instead of inventing starter-local validation infrastructure.

## Module Authoring

- Use `modules/Users/` as the reference shape for new starter modules.
- Keep each module self-contained under `modules/<Name>/` and feature-based.
- The default module folder layout may include `manifest.php`, a service provider, `routes/http.php`, `Http/Controllers/`, `Models/`, `resources/views/`, `database/migrations/`, `database/seeders/`, `Support/`, and, when truly needed, `config/`, `Policies/`, `Actions/`, `Entities/`, `Widgets/`, and small services.
- Keep controllers thin. Put validation rules, query coordination, form shaping, and other app-specific logic into focused support classes only when it materially reduces duplication.
- Prefer framework-native features first: router groups, controllers, middleware, Twig views, model APIs, events, config, and helper functions. Do not recreate these as starter-local infrastructure.
- Do not add wrapper layers around framework services just to normalize style. Add app code only when the behavior is specific to this starter or module.
- Use manifest `paths.views` for module Twig namespaces; the framework auto-registers them. Do not manually call `addNamespace()` in service providers.
- Keep module manifests explicit. If a module has migrations, add them to the manifest `migrations` list so cached installs can still discover them.
- Each module manifest should stay explicit about `name`, `slug`, `version`, `providers`, `routes`, `migrations`, and, when used by the module, dependencies, permissions, widgets, menu, and status metadata.
- Prefer direct `ActivityRecorder` calls for starter-local activity logging unless there is a real multi-listener need. Treat true global lifecycle behavior as a framework concern instead of adding app-local event indirection by default.
- CSRF, auth, theme selection, routing, and request middleware should follow the existing starter patterns instead of inventing per-module alternatives.
- New module tests should cover starter wiring and user-visible behavior, not framework internals already owned by `vendor/memran/marwa-framework/`.
- Keep modules decoupled and reusable. Declare dependencies explicitly and load-independent modules before dependent modules through manifest metadata rather than ad hoc runtime glue.

## Policy

- Use framework authorization primitives and policies rather than inline permission logic spread across controllers.
- Keep policies focused on resource authorization decisions such as `viewAny`, `view`, `create`, `update`, and `delete`.
- Register or resolve policies through framework conventions instead of inventing a starter-local authorization layer.
- Put app-specific authorization rules in starter or module policy classes only when they are truly application-owned.

## Menu Navigation

- Use the framework navigation/menu registry for shared application navigation.
- Module navigation should be declared through module manifests or provider boot logic, not hardcoded across unrelated controllers or views.
- Keep menu items permission-aware and visibility-aware using framework helpers and shared auth state.
- Treat the final main menu tree as shared application state that views consume, not something each controller rebuilds.

## Validation

- `Marwa\Support\Validation` is the canonical validation engine; use framework adapters and helpers on top of it.
- Prefer `validate_request()` or controller validation helpers for straightforward request validation.
- Use reusable validation objects or focused support classes only when they remove real duplication.
- Validation failures should follow framework flash conventions for `errors` and `_old_input` instead of custom starter-local mechanisms.
- If schema and validation overlap materially, prefer `marwa-entity` as the source of truth rather than duplicating rules across layers.

## View

- Use the framework view service and `view()` helper for rendering.
- Keep theme-aware templates under `resources/views/themes/` and module-owned templates under the module namespace registered by the module service provider.
- Use view config for active theme, fallback theme, cache path, and theme paths; do not move theme selection into session state.
- Share global view data, namespaces, and menu trees through framework view and provider hooks instead of per-controller duplication.

## Database

- Use framework and `marwa-db` configuration, models, migrations, and seeders instead of custom database abstractions.
- Keep application migrations under `database/migrations` and module migrations under each module’s `database/migrations`, with explicit manifest `migrations` entries for module caches.
- Use `config/database.php` for starter-owned overrides such as the SQLite-first starter setup, migration path, and seeder path.
- Prefer framework database commands and migration/seeder conventions before adding starter-local database workflow code.
- Keep starter database behavior app-specific; if a DB workaround would help multiple Marwa apps, surface it as a framework gap.

## Testing Scope

- Test starter behavior only: routes, custom middleware, starter commands if any, config integration, and custom module wiring.
- Do not add tests for framework internals, framework commands, or framework view/theme mechanics that are already covered in `vendor/memran/marwa-framework/`.
- Prefer behavior-level assertions that exercise real requests and responses.
- Every new public starter service method should have app-level test coverage when it owns behavior not already guaranteed by the framework.

## Commands

- `composer install` installs dependencies.
- `composer test` runs the starter PHPUnit suite.
- `composer analyse` runs PHPStan for this starter.
- `php marwa` runs the Marwa CLI for local manual checks.

## Style

- Use `declare(strict_types=1);`.
- Follow PSR-1, PSR-12, and PSR-4.
- Use 4-space indentation.
- Prefer typed properties and explicit return types.
- Use PascalCase for classes and conventional suffixes like `*Interface`, `*Exception`, and `*ServiceProvider`.
- Prefer small, single-purpose classes and keep controllers, middleware, and support classes focused.
- Use constants or enums for finite states when that improves clarity.
- Keep files small: max 200 lines per class, 20 lines per method

## Engineering Principles

- KISS, DRY, and SOLID apply, but prefer the thinnest starter implementation that follows framework conventions.
- Understand the existing app and framework context before coding.
- Prefer composition over inheritance and keep features modular and decoupled.
- Keep changes minimal, scoped, and backward compatible for starter consumers.
- Edit existing code instead of creating duplicates when the current structure already fits.
- Validate and sanitize user input with framework or support-layer primitives rather than ad hoc logic.
- Favor readability and maintainability over cleverness or premature optimization.
- Treat reusable cross-app behavior as a framework concern and starter-specific behavior as an app concern.
- donot run raw sql query.Use ORM or Query Builder

## Testing

- Add starter tests in `tests/` using `*Test.php`.
- Test starter behavior only: routes, bootstrapping, custom middleware, starter commands, config integration, and module wiring.
- Do not add tests for framework internals already covered by `vendor/memran/marwa-framework/`.
- Prefer behavior-level assertions using real requests and responses where practical.
- Every new public starter service method should have app-level test coverage when it owns behavior not already guaranteed by the framework.
- Run `composer test`, then `composer analyse`.

## Documentation

- Keep `README.md` synchronized with the actual starter behavior.
- Document `composer create-project`, quick start, project structure, routing, controller, view usage, and the split between app code and framework code.
- Keep `docs/module-authoring.md` aligned with the actual module conventions used in this starter.
- When documenting shared UI assets or dependencies, describe the real implementation precisely. Do not say a package or build pipeline is used unless it is actually installed, wired, and shipped by the starter.
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
