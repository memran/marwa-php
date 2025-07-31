# 📝 Input API in MarwaPHP

The `Input` facade in MarwaPHP provides a convenient interface for accessing HTTP request data, whether it comes from query parameters, form submissions, JSON payloads, or headers.

---

## 📥 Retrieving Input Data

### `Input::get($key, $default = null)`

Retrieve a value from the request input (GET, POST, or JSON body):

```php
$name = Input::get('name');
$email = Input::get('email', 'default@example.com');
```

---

## 🧪 Checking for Input Keys

### `Input::has($key)`

Check whether a specific key exists:

```php
if (Input::has('email')) {
    // do something
}
```

---

## 📦 Working with All Input

### `Input::all()`

Returns all input data as an array:

```php
$data = Input::all();
```

### `Input::only(array $keys)`

Returns only selected fields:

```php
$user = Input::only(['name', 'email']);
```

### `Input::except(array $keys)`

Returns input data excluding specified keys:

```php
$clean = Input::except(['password']);
```

---

## 🔐 JSON & Raw Payloads

### `Input::json()`

Get the request body parsed as JSON:

```php
$payload = Input::json();
```

### `Input::isJson()`

Check if the request expects or sends JSON:

```php
if (Input::isJson()) {
    // return JSON response
}
```

---

## 🧠 HTTP Context Helpers

### `Input::method()`

Returns the HTTP request method (`GET`, `POST`, etc.):

```php
$method = Input::method();
```

### `Input::ip()`

Returns the client IP address:

```php
$ip = Input::ip();
```

### `Input::url()`

Returns the full request URL:

```php
$url = Input::url();
```

### `Input::header($key)`

Get a specific request header:

```php
$auth = Input::header('Authorization');
```

---

## 🧾 File Uploads

(If supported internally — inferred)

### `Input::file($key)`

Get uploaded file metadata or instance:

```php
$file = Input::file('avatar');
```

---

## 💡 Best Practices

- Use `Input::get()` to retrieve general values.
- Use `Input::json()` for API endpoints accepting JSON.
- Validate input data before processing.
- Use middleware for CSRF protection, input sanitization, and authentication.

---

> 📘 The Input API in MarwaPHP is modeled after Laravel’s request handling, but tailored for a leaner, faster framework.
