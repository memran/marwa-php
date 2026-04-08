# Config

This directory contains the base framework configuration files used by the app.

## Files

- `app.php` - application identity, providers, middleware, and debug defaults
- `bootstrap.php` - config, route, and module cache paths; module cache normalization stays here
- `cache.php` - cache backend defaults and storage options
- `console.php` - console app name, version, and command discovery, including `app/Commands`
- `database.php` - database connections and migration/seed paths
- `error.php` - error handler defaults and renderer settings
- `event.php` - event listeners and subscribers
- `http.php` - HTTP client defaults
- `logger.php` - log storage, filters, and channel defaults
- `mail.php` - mail transport and sender defaults
- `module.php` - module enablement, discovery, and manifest cache
- `notification.php` - notification channels and defaults
- `queue.php` - queue path and retry settings
- `schedule.php` - scheduler driver and lock paths
- `security.php` - CSRF, throttle, and risk-report settings
- `session.php` - session storage, lifetime, and cookie defaults
- `storage.php` - storage disk defaults
- `view.php` - view paths, debug/cache behavior, and theme defaults

## Rule Of Thumb

- Put framework defaults here first.
- Keep app code thin and override only when the app truly needs a different value.
- If the same setting keeps showing up in controllers or views, it probably belongs in `config/` or the framework itself.
