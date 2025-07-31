# 🧰 Helpers in MarwaPHP

MarwaPHP offers a rich set of global helper functions designed to streamline development. These are accessible throughout your controllers, routes, middleware, and services.

---

## 📦 Environment & Config

### `env(key, default = null)`
Get a value from `.env`.

```php
$mode = env('APP_ENV', 'production');
```

### `config(key)`
Access config values using dot notation.

```php
$timezone = config('app.timezone');
```

---

## 📄 Path Helpers

These resolve full paths to important directories:

| Function            | Description                          |
|---------------------|--------------------------------------|
| `base_path($path)`  | Returns full path from base dir      |
| `config_path($path)`| Path to config directory             |
| `public_path($path)`| Path to public directory             |
| `resource_path($p)` | Path to resources directory          |
| `storage_path($p)`  | Path to storage                      |

```php
$logs = storage_path('logs/laravel.log');
```

---

## 🔐 Security & Session

### `csrf_token()`
Returns the current CSRF token.

```php
<input type="hidden" name="_token" value="<?= csrf_token() ?>">
```

### `session($key = null)`
Access session data or flash variables.

```php
session()->put('user_id', 123);
$userId = session('user_id');
```

---

## 🔄 Redirect & Response

### `redirect($url)`
Issue an HTTP redirect.

```php
return redirect('/dashboard');
```

### `abort($code, $message = '')`
Immediately stop and return HTTP error.

```php
abort(404, 'Page not found');
```

---

## 🎨 View & URL

### `view($name, $data = [])`
Render a Twig template.

```php
return view('welcome', ['user' => $user]);
```

### `asset($path)`
Get the full URL of a public asset.

```php
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
```

---

## 🔧 Utility Helpers

### `app($abstract = null)`
Access the container or resolve services.

```php
$logger = app('logger');
```

### `request()`
Get the current HTTP request object.

```php
$request = request();
$ip = request()->ip();
```

### `response($data, $status = 200)`
Return a response with optional status.

```php
return response(['message' => 'OK'], 200);
```

---

## 🧪 Additional Utilities

### `collect(array)`
Create a collection from an array.

```php
$users = collect(['Alice', 'Bob'])->map(fn($n) => strtoupper($n));
```

### `now()`
Get current time as a Carbon object.

```php
$today = now()->toDateString();
```

---

> 🧠 Most helpers follow Laravel’s conventions but are adapted for MarwaPHP's lightweight PSR architecture.
