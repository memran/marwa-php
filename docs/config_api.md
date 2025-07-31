# ⚙️ Configuration API in MarwaPHP

MarwaPHP uses a flexible, PSR-compliant configuration system inspired by Laravel, but optimized for micro-framework performance. It supports loading configuration from PHP array files stored in the `config/` directory and from environment variables using the `.env` file.

---

## 📁 Configuration Files

All configuration files reside in the `config/` directory and return associative arrays. You can organize your configuration modularly, like so:

```text
config/
├── app.php
├── database.php
├── mail.php
├── cache.php
├── queue.php
└── custom.php
```

---

## 🧩 Loading Config Files

You can load configuration files via:

```php
use Marwa\Application\Facades\Config;

$config = Config::load('app.php');
```

Or use the service container approach:

```php
$config = app('config')->file('app.php')->load();
```

---

## 🔍 Accessing Config Values

You can get or set specific config values:

```php
// Get a value
$debug = Config::get('app.debug');

// Set a value at runtime
Config::set('app.locale', 'fr');

// Check if config exists
if (Config::has('database.default')) {
    // Logic
}
```

Supports dot notation for nested keys.

---

## 🛠 Custom Config Files

You can create your own configuration files easily:

```php
// config/payment.php
return [
    'provider' => 'stripe',
    'api_key'  => env('STRIPE_API_KEY'),
];
```

Access in code:

```php
$provider = Config::get('payment.provider');
```

---

## 🔐 Environment Variables

Use `env()` to access values from `.env`:

```php
$env = env('APP_ENV', 'production');
$port = env('DB_PORT', 3306);
```

This allows sensitive or instance-specific values to remain outside version control.

---

## 🧰 Useful Helpers

- `env('KEY', 'default')` – Get env value with fallback.
- `config('app.name')` – Shortcut to retrieve a config value.
- `app('config')` – Service container access to config manager.

---

## 📦 Caching Configuration (Planned Feature)

In future releases, config caching will be available for performance:

```bash
php marwa config:cache
php marwa clear:cache
```

This will compile all config into a single file to improve bootstrapping speed.

---

## 💡 Best Practices

- Group related configs (e.g., mail, queue, database).
- Use `env()` in config files, not inside controllers.
- Avoid hardcoding secrets — use `.env`.

---

> 🧠 MarwaPHP configuration is optimized for clarity, flexibility, and environment portability.
