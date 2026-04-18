# Architecture

This repository is the app-level implementation of `marwa-framework`.

The main rule is simple: keep the app thin and use the framework's own design, helpers, and extension points first. If a behavior is repeated across modules or controllers, it likely belongs in the framework or one of its companion packages.

## Layer Map

### `marwa-framework`
Owns the core runtime.

- application bootstrap
- container wiring
- environment loading
- config defaults and normalization
- HTTP kernel and middleware pipeline
- controller base helpers
- route bootstrapping
- console kernels and shared commands
- framework-level cache handling
- framework-level debug and lifecycle events

### `marwa-module`
Owns module discovery and module lifecycle integration.

- module manifest parsing
- module registry and lookup
- module cache
- module service provider bootstrap
- module path resolution
- module route and view registration contracts

### `marwa-view`
Owns rendering behavior.

- Twig rendering
- namespaced view roots for modules

### This app repo
Owns application composition only.

- public entrypoints
- app routes
- app controllers
- app-specific views and overrides
- environment defaults and app-specific config overrides for this product
- module wiring for this product
- starter-specific module workflows and support classes

## Current Boot Flow

1. `public/index.php` creates `Marwa\Framework\Application`.
2. `Application` loads `.env` and registers core services.
3. `AppBootstrapper` loads framework defaults plus any starter config overrides from `config/`.
4. `ModuleBootstrapper` loads module manifests, providers, views, and routes.
5. `HttpKernel` runs the middleware pipeline and router dispatch.
6. `ViewAdapter` resolves Twig templates and renders the response.

The CLI entrypoint follows the same application bootstrap pattern through `marwa`.

## Conventions To Follow

- Prefer `env()` and framework helpers such as `base_path()`, `storage_path()`, `cache_path()`, `resources_path()`, and `module_path()`.
- Keep controller code thin and extend `Marwa\Framework\Controllers\Controller`.
- Keep app routes declarative and avoid bootstrapping framework behavior inside route files.
- Keep caches under `storage/`.
- Make configuration the source of truth for app defaults.
- Keep `config/` limited to starter-specific overrides; rely on framework defaults when the starter is not intentionally changing behavior.

## App Responsibilities

### Controllers
- Use controller types to separate frontend and backend rendering concerns.
- Keep page controllers focused on rendering and request handling.
- Use framework helpers for validation, redirects, flash data, and view rendering.

### Routes
- Keep route declarations in `routes/web.php` and `routes/api.php`.
- Module routes should live in module route files and be loaded through module manifests.

### Modules
- Each module should keep its own manifest, provider, routes, views, migrations, and seeders when needed.
- Modules should register through the framework module bootstrap, not through ad hoc app wiring.
- Starter-local activity logging should prefer direct `ActivityRecorder` calls unless a real fan-out need appears.

### Config
- `config/app.php` should contain starter-owned overrides such as the maintenance and 404 templates and the starter-specific debugbar toggle behavior.
- Theme defaults belong in `config/view.php`.
- Module cache and module enablement belong in `config/module.php`.
- Session defaults should come from the framework unless the starter has a real app-specific override.

### Testing
- Tests in this repo should verify starter-owned behavior only.
- Do not lock tests to framework internal config shape, framework default middleware lists, or framework default extension lists.
- Prefer feature coverage for routes, theme resolution, auth flow, module wiring, and other user-visible starter behavior.

## Framework Gaps To Consider Upstream

If the app keeps needing the same workaround more than once, the framework probably needs a new capability.

- a first-class frontend/backend controller abstraction
- safer config list merging for middleware/provider overrides without restating full framework defaults
- configurable default session save path so apps and tests do not depend on the machine-wide PHP session path
- clearer documentation for boot order and extension points

## Working Rule

If a change can be expressed cleanly in the framework or a companion package, do that first.
If it only belongs to this app, keep it local.

## Practical Default

When in doubt:

- ask whether the code belongs in the framework first
- keep the app implementation minimal
- prefer manifest/config-driven behavior over hardcoded glue
- treat recurring app-side convenience code as a candidate for upstreaming
