# Repository Guidelines

## Purpose
- This repository is the application implementation of `marwa-framework`.
- Follow the framework's design, helpers, lifecycle, and extension points first.
- If a feature is missing from the framework, prefer suggesting a framework change over adding a one-off app workaround.
- Only patch framework internals in this repo when it is required for the current task or when the change is clearly meant to be upstreamed.

## Architecture Principles
- Keep the app thin and framework-aligned.
- Prefer framework helpers such as `env()`, `app_path()`, `base_path()`, `cache_path()`, `public_path()`, `resources_path()`, and `storage_path()` over hardcoded paths or raw globals.
- Use the framework's configuration, view, routing, and module conventions before inventing new patterns.
- Treat repeated local hacks as a signal that the framework should gain a capability.
- Keep cross-cutting behavior in shared support classes or framework code, not duplicated inside controllers or views.

## Project Structure
- `app/` contains app-level controllers and support code under the `App\\` namespace.
- `config/` holds runtime configuration for app, view, server, module, logger, and event behavior.
- [config/README.md](config/README.md) lists the base framework config files and what they control.
- `routes/` defines HTTP routes for the application entry points.
- `modules/` contains optional feature modules and their manifests, routes, controllers, views, models, migrations, and seeders.
- `resources/views/` contains Twig views and shared partials.
- `public/` is the web root and exposes compiled assets.
- `database/` contains migrations and seeders.
- `tests/` contains unit and feature tests for behavior coverage.

## Theme and Module Rules
- Theme selection comes from configuration, not from session state.
- Modules should remain self-contained with their own manifest, routes, controllers, views, migrations, and seeders when applicable.
- When a module needs behavior that is missing from the framework, raise the framework-level change instead of hardcoding module-specific glue.

## Build, Test, and Development Commands
- `composer install` installs PHP dependencies and sets up autoloading.
- `composer test` runs the PHPUnit suite.
- `composer analyse` runs PHPStan.
- `composer lint` checks PHP syntax across the repository.
- `composer fix` runs PHP-CS-Fixer.
- `composer ci` runs the local validation chain used by CI.
- `php -S localhost:8000 -t public/` runs the app locally using `public/` as the document root.
- `npm run dev` watches and rebuilds Tailwind CSS from `resources/css/app.css` into `public/assets/css/app.css`.
- `npm run build` produces the production stylesheet.

## Coding Style & Naming Conventions
- Follow PSR-4 autoloading for classes in `app/`.
- Use `StudlyCaps` for class names and `camelCase` for methods and variables.
- Keep Twig templates lowercase and grouped by feature, for example `resources/views/home/index.twig`.
- Use 4-space indentation in PHP and Twig unless the surrounding file already uses a different convention.
- Keep changes consistent with nearby code; there is no project-wide formatter beyond the existing tooling.

## Testing Guidelines
- `phpunit.xml` defines `Unit` and `Feature` suites and expects `*Test.php` files under `tests/Unit` and `tests/Feature`.
- Add tests when controller, route, module, theme, config, or middleware behavior changes.
- Prefer behavior-level assertions over framework internals.
- If a change requires framework behavior that is not easy to test from the app layer, call that out explicitly.

## Framework Change Guidance
- If the best fix belongs in `marwa-framework`, say so clearly in your response.
- Keep framework suggestions concrete: describe the class, helper, config key, or lifecycle hook that should change.
- Avoid burying framework shortcomings in app code when the same fix would help every consumer.
- If a local patch is unavoidable, document why it is temporary or why it should be upstreamed.

## Commit & Pull Request Guidelines
- Use short, imperative commit subjects such as `Fix issue` or `Update composer`.
- Keep PR summaries clear, explain why the change was made, and include manual verification steps.
- Attach screenshots or request/response examples when UI, view, or API output changes.

## Security & Configuration Tips
- Never commit secrets or machine-specific values.
- Keep environment values in `.env` and update `.env.example` when defaults change.
- Review changes to `config/`, `routes/`, and `public/` carefully because they affect startup and exposed endpoints.
