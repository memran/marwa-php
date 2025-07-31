# ⚙️ Configuration API

MarwaPHP uses a powerful and simple configuration system. All configuration files are placed in the `config/` directory. These files return associative arrays and can be accessed using the `Config` facade or the `app()` helper.

---

## 📂 Loading a Configuration File

You can load any config file using the `Config` facade:

```php
use Marwa\Application\Facades\Config;

$config = Config::load('app.php');
```

Alternatively, use the `app()` helper:

```php
$config = app('config')->file('app.php')->load();
```

---

## 🔍 Accessing Configuration Values

You can access specific keys using:

```php
$value = Config::get('app.name');
```

Or set values at runtime:

```php
Config::set('app.debug', false);
```

---

## 🛠 Creating Custom Configuration Files

You can create your own config files in the `config/` directory.

For example, create `config/mailer.php`:

```php
return [
    'smtp_host' => 'smtp.mailtrap.io',
    'port'      => 2525,
    'username'  => 'your-user',
    'password'  => 'your-pass',
];
```

Access it using:

```php
$mailer = Config::load('mailer.php');
echo $mailer['smtp_host'];
```

---

## 🔐 Using Environment Variables

MarwaPHP reads `.env` variables using the `env()` helper:

```php
$db_user = env('DB_USER');
```

This allows flexible, environment-based settings without hardcoding sensitive values.

---

## 🚀 Caching Configuration (Optional)

To improve performance, you can preload and cache configuration values during the bootstrap phase by writing custom logic or CLI commands (coming soon).
