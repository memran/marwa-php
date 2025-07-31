# 🧰 Helpers in MarwaPHP

MarwaPHP provides a collection of global helper functions to simplify common operations. These helpers can be used throughout controllers, services, routes, or anywhere in your application.

---

## 🔧 Common Helpers

### `env()`

Reads environment variables from the `.env` file.

```php
$debug = env('APP_DEBUG', false);
```

---

### `config()`

Access configuration values directly.

```php
$appName = config('app.name');
```

---

### `view()`

Render a Twig view.

```php
return view('home', ['title' => 'Welcome']);
```

---

### `asset()`

Generate a public asset URL from the `public/` directory.

```php
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
```

---

### `app()`

Access the service container or resolve a bound class.

```php
$logger = app('logger');
```

---

### `base_path()`, `config_path()`, `public_path()`, `resource_path()`, etc.

Get the full system path for various directories.

```php
$path = base_path('storage/logs');
```

---

### `redirect()`

Perform HTTP redirects.

```php
return redirect('/login');
```

---

### `abort()`

Stop the request and return a given HTTP status.

```php
abort(403, 'Unauthorized action.');
```

---

### `csrf_token()`

Retrieve the CSRF token string.

```php
<input type="hidden" name="_token" value="<?= csrf_token() ?>">
```

---

> 🧠 These helper functions are designed for convenience and are globally available throughout your MarwaPHP application.
