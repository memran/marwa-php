---
layout: default
title: Configuration
---

# Configuration

The starter keeps configuration narrow and app-specific.

## Files

- `config/app.php` sets the application name, providers, middleware stack, and starter maintenance / 404 templates
- `config/database.php` enables SQLite by default so the Auth and Users modules work on a fresh starter install
- `config/event.php` wires the module migration bootstrap listener
- `config/console.php` discovers app commands from `app/Commands`
- `config/view.php` defines the frontend and admin theme names plus view cache defaults

## Environment Values

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

## Rule Of Thumb

- Keep overrides here only when the starter needs a different value from the framework default
- Do not restate framework defaults that are already handled in Marwa
- If the same value keeps appearing in controllers or views, move it into configuration instead of duplicating it
