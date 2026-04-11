---
layout: default
title: Routing, Controllers, and Views
---

# Routing, Controllers, and Views

## Routes

`routes/web.php` defines the front page and the admin dashboard route group.
`routes/api.php` defines the starter health endpoint.

## Controllers

- Keep controllers thin
- Extend `Marwa\Framework\Controllers\Controller`
- Use framework helpers for validation, redirects, flash data, and view rendering

## Admin Theme Middleware

`app/Http/Middleware/AdminThemeMiddleware.php` switches the active theme while admin routes are handled.
The middleware reads the theme name from configuration so theme selection stays config-driven.

## Views

- Frontend theme views live under `resources/views/themes/default/`
- Admin theme views live under `resources/views/themes/admin/`
- Shared theme components live under `resources/views/components/`

## Starter Defaults

- `resources/views/themes/default/views/maintenance.twig` is the maintenance page template
- `resources/views/themes/default/views/errors/404.twig` is the not-found template
- `config/app.php` points the framework at those templates
