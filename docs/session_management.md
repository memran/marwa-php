# 🧠 Session Management in MarwaPHP

Session management in MarwaPHP provides a secure and flexible way to store temporary user data across multiple requests — such as login states, flash messages, user preferences, and more.

---

## 🚀 Enabling Session

Sessions are enabled by default. Configuration is done in:

```php
config/session.php
```

### Default Config Example

```php
return [
    'driver' => 'file', // Options: file, redis
    'lifetime' => 120,
    'path' => '/',
    'domain' => null,
    'secure' => false,
    'http_only' => true,
    'same_site' => 'Lax'
];
```

---

## 🧰 Available Session Drivers

| Driver | Description               |
|--------|---------------------------|
| file   | Stores sessions in /storage/sessions |
| redis  | High-performance storage (configure Redis) |

---

## 🧪 Basic Usage

### Set Session Data

```php
session()->set('user_id', 42);
```

### Get Session Data

```php
$id = session()->get('user_id');
```

### Check for Session Key

```php
if (session()->has('user_id')) {
    // Key exists
}
```

### Remove a Key

```php
session()->remove('user_id');
```

### Flash Data (one-time only)

```php
session()->flash('success', 'Profile updated successfully!');
```

### Retrieve Flash Data

```php
echo session()->get('success'); // Available for one request only
```

---

## 👤 Authenticated User Sessions

You may store user objects or IDs after login:

```php
session()->set('user', $user);
```

On each request:

```php
$user = session()->get('user');
```

Be cautious with storing sensitive data (e.g., password hashes).

---

## 🛡 Security Best Practices

- Use HTTPS and enable `'secure' => true`
- Enable `'http_only' => true` to prevent JavaScript access
- Consider Redis for distributed sessions
- Always regenerate session ID after login:

```php
session()->regenerate();
```

---

## 🧹 Session Cleanup

If using the file driver, cleanup is handled via:

```bash
php cli session:cleanup
```

Or via cron:

```bash
* * * * * php cli session:cleanup
```

---

## 🧪 Testing Sessions

Use mocks or `session()->set()` in test setup files. Sessions persist within the same request lifecycle when testing controllers or middleware.

---

## 🧠 Summary

| Function             | Example                             |
|----------------------|-------------------------------------|
| Set value            | `session()->set('key', 'value')`    |
| Get value            | `session()->get('key')`             |
| Flash once           | `session()->flash('msg', 'done')`   |
| Check existence      | `session()->has('key')`             |
| Remove key           | `session()->remove('key')`          |
| Regenerate session   | `session()->regenerate()`           |
| Cleanup files        | `php cli session:cleanup`           |

---

🎉 MarwaPHP session management keeps your app secure and stateful without hassle. Combine it with Middleware for powerful access control!
