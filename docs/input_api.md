# 📝 Input Handling in MarwaPHP

MarwaPHP provides a clean and straightforward way to handle HTTP request input through the `Input` facade. It helps you easily retrieve GET, POST, PUT, or JSON payloads.

---

## 📥 Retrieving Input Data

Use the `Input::get()` method to retrieve values from the request:

```php
use Marwa\Application\Facades\Input;

$name = Input::get('name');
$email = Input::get('email', 'default@example.com'); // with default fallback
```

---

## 🧪 Checking for Input Existence

You can verify if a key exists:

```php
if (Input::has('username')) {
    // Do something
}
```

---

## 📦 Retrieving All Input Data

```php
$all = Input::all();
```

You can also get only specific fields:

```php
$data = Input::only(['email', 'password']);
```

Or exclude specific fields:

```php
$data = Input::except(['_token']);
```

---

## 🔐 Handling JSON Requests

If you're receiving JSON, you can parse it like this:

```php
$jsonData = Input::json();
```

---

## 🔍 Additional Features

- `Input::isJson()` — Check if the incoming request is JSON.
- `Input::method()` — Returns the HTTP request method (GET, POST, etc.).
- `Input::ip()` — Get the client's IP address.
- `Input::url()` — Get the request URL.

---

> 🔔 The Input facade is available globally within controllers, middleware, and services in MarwaPHP.
