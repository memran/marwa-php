---
layout: default
title: Project Structure
---

# Project Structure

## App Code

- `app/Http/Controllers/` thin application controllers
- `app/Http/Middleware/` starter-specific middleware, such as admin theme switching
- `app/Listeners/` app-level event listeners, including module migration bootstrap

## Configuration

- `config/` starter overrides for app identity, middleware, theme names, and error pages
- `config/database.php` enables SQLite by default so the Auth and Users modules work on a fresh install

## HTTP Entry Points

- `routes/web.php` frontend and admin routes
- `routes/api.php` API routes

## Views

- `resources/views/themes/default/` frontend theme layout and views
- `resources/views/themes/admin/` admin theme layout and views
- `resources/views/components/` shared Twig partials used by the frontend theme
- `resources/views/themes/default/views/maintenance.twig` default maintenance page
- `resources/views/themes/default/views/errors/404.twig` default 404 page

## Modules and Data

- `modules/` optional feature modules, including Auth, Users, Activity, Notifications, Settings, and DashboardStatus
- `database/seeders/` starter seeders, including the first admin account used by the auth module
- `database/` database files and migrations used by the starter

## Tests

- `tests/` app-specific PHPUnit coverage

## Rule Of Thumb

If code is reusable across Marwa applications, it usually belongs in the framework or a companion package.
If it is starter-specific or product-specific, keep it here.
