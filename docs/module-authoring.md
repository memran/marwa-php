# Module Authoring Guide

This starter treats `modules/Users/` as the reference example for writing new modules.
The goal is not to create a mini-framework inside the app. The goal is to keep modules thin, explicit, and aligned with Marwa Framework conventions.

## Core Rules

- Use Marwa helpers and services first: `env()`, `config()`, `base_path()`, `storage_path()`, `view()`, router groups, controllers, middleware, and the framework model APIs.
- Keep module code self-contained under `modules/<Name>/`.
- Add app code only when it is truly starter-specific or module-specific.
- If a missing capability would be useful across multiple Marwa apps, treat it as a framework gap instead of hiding it with large starter abstractions.
- Keep controllers small and explicit. Move repeated app-specific logic into focused support classes, not broad wrapper layers.
- Test starter wiring and user-visible behavior only. Do not duplicate framework test coverage here.

## Canonical Module Shape

Use this layout when a module needs routes, views, and persistence:

```text
modules/
  Blog/
    manifest.php
    BlogServiceProvider.php
    routes/
      http.php
    Http/
      Controllers/
    Models/
    Support/
    resources/
      views/
    database/
      migrations/
      seeders/
```

Not every module needs every directory.

- `manifest.php` is required.
- A service provider is required when the module needs boot logic such as view namespaces.
- `routes/http.php` is needed when the module exposes HTTP endpoints.
- `Models/` is needed when the module persists data.
- `resources/views/` is needed when the module renders Twig templates.
- `database/seeders/` is optional.
- `Support/` is optional and should stay small.

## Manifest Conventions

Every module should define a clear manifest with:

- `name`
- `slug`
- `version`
- `providers`
- `paths`
- `routes`
- `migrations` when the module ships migrations

Follow the shape already used by `Users`, `Auth`, and `Activity`.

Important: when a module has migrations, add explicit file paths to the manifest `migrations` list. Do not rely only on `paths['database/migrations']`. This starter can cache module metadata, and cached installs need explicit migration entries to discover module migrations reliably.

## Service Provider Conventions

Module service providers should stay minimal.

- Use them to register view namespaces or other small module bootstrapping behavior.
- Avoid putting business logic in the service provider.
- Avoid container glue unless the module actually needs it.

The `UsersServiceProvider` is the reference example: it only adds a Twig namespace during web requests.

## Routing And Controllers

Use `routes/http.php` with framework router groups and middleware.

- Group related routes under a stable prefix.
- Apply existing middleware patterns instead of creating module-specific alternatives without need.
- Use controller classes under `Http/Controllers`.
- Keep controllers thin: validate input, coordinate services, choose the response, and stop there.

For CRUD-style modules, follow the `Users` pattern:

- list controller
- create controller
- store controller
- show controller when needed
- edit controller
- update controller
- delete controller
- restore controller when soft deletes are used

Controller responsibilities should stay narrow:

- read request input
- call validation rules
- coordinate the model or a small support class
- flash state and redirect or render a view

## Models

Use `Marwa\Framework\Database\Model` for module persistence.

- Keep fillable fields, casts, and soft-delete flags on the model.
- Put persistence-related behavior on the model.
- If a module needs lifecycle hooks, keep them small and module-specific.

Do not try to rebuild framework-wide lifecycle infrastructure in the app. If you need cross-module automatic behavior, document that as a framework-level need.

## Support Classes

Use `Support/` only when it reduces duplication and keeps controllers thin.

Good examples:

- validation rules
- form data shaping
- query coordination
- small repository classes when they add app-specific value
- activity payload formatting

Avoid turning `Support/` into a second framework layer.

## Views

Module views should live under `resources/views/` and be loaded through a module namespace registered by the module service provider.

- Reuse the existing admin theme layout and shared partials.
- Use framework-managed CSRF helpers in unsafe forms.
- Follow the current starter HTML, Twig, and Tailwind patterns instead of creating a new UI language per module.
- Keep forms and index views explicit and readable.

## Database And Seeders

Put module migrations under `database/migrations/`.

- Keep migration file names explicit and ordered.
- Add each migration file to the manifest `migrations` list.
- Use seeders only when the module has starter-specific bootstrap data.

The `Users` module seeder is a starter-specific bootstrap example, not a rule that every module needs seeded data.

## Events And Activity

Use app events and listeners only when the workflow genuinely needs fan-out or decoupling.

- Prefer direct `ActivityRecorder` calls for starter-local activity logging.
- Avoid duplicating the same side effect across multiple controllers.
- If using model hooks for a module, keep the hook registration minimal and justify it with a real need beyond simple controller or repository coordination.

True global model event bridging belongs in the framework, not in this starter.

## Testing Expectations

New modules should add tests only for starter-owned behavior.

- feature tests for routes, middleware wiring, redirects, responses, and rendered content
- unit tests for starter-specific support logic when that logic is non-trivial
- no tests that restate framework routing, ORM, Twig, or event internals

Use behavior-level assertions whenever possible.

## Authoring Checklist

When adding a new module, verify all of the following:

- the module stays self-contained under `modules/<Name>/`
- `manifest.php` is complete and explicit
- migrations are listed in the manifest
- controllers are thin
- support classes are small and justified
- views use the existing theme and CSRF patterns
- tests cover starter behavior only
- README is updated if the module changes user-facing starter behavior
- `AGENTS.md` is updated only if the repository-wide convention changed

## Recommended Starting Point

If you are unsure how to structure a new module, copy the decision pattern from `Users`:

- manifest with explicit migrations
- minimal service provider
- route file with grouped admin routes
- thin controllers
- model with only persistence concerns
- small support classes for validation and view shaping
- Twig views under a module namespace
- focused starter tests

That is the current best-practice pattern for this repository.
