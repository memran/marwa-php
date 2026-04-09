# Config

This directory contains the app-specific overrides that sit on top of Marwa Framework defaults.

## Files

- `app.php` - application identity, providers, middleware, debugbar collectors, and starter maintenance / 404 template references
- `cache.php` - cache backend defaults and storage options
- `console.php` - app command discovery from `app/Commands`
- `database.php` - database connections and migration/seed paths
- `error.php` - error handler defaults, logger/debug reporter toggles, and renderer settings
- `event.php` - event listeners and subscribers
- `http.php` - HTTP client defaults
- `logger.php` - log storage, filters, channel defaults, and log level/prefix defaults
- `mail.php` - mail transport and sender defaults
- `notification.php` - notification channels and defaults
- `queue.php` - queue path and retry settings
- `schedule.php` - scheduler driver and lock paths
- `security.php` - CSRF, throttle, and risk-report settings
- `session.php` - session storage, lifetime, and cookie defaults
- `storage.php` - storage disk defaults
- `view.php` - starter theme names and view cache defaults used by the frontend and admin areas

## Rule Of Thumb

- Keep overrides here only when the starter needs a different value from the framework default.
- If a setting is already handled by Marwa defaults, do not re-declare it here.
- If the same value keeps showing up in controllers or views, it probably belongs in `config/` or the framework itself.
