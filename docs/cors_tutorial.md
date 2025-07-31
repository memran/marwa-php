# 🌐 Handling Cross-Origin Requests (CORS) in MarwaPHP

Cross-Origin Resource Sharing (CORS) is essential when building web applications using modern frontend frameworks like **Vue.js** or **Angular**. These frameworks typically run on a different domain or port during development and need explicit permission to communicate with your MarwaPHP backend.

This tutorial walks you through setting up a **CORS middleware** in MarwaPHP to support API requests from frontend apps.

---

## 🎯 What is CORS?

CORS is a security mechanism implemented in web browsers to prevent unauthorized cross-origin HTTP requests. When your frontend (e.g., `http://localhost:4200` or `http://localhost:5173`) tries to access the backend (e.g., `http://localhost:8000`), the browser sends a **preflight request** using the `OPTIONS` method.

To allow this communication, your backend must include specific HTTP headers.

---

## 🛠 Step 1: Create the CORS Middleware

Generate a new middleware file:

```bash
php marwa make:middleware CorsMiddleware
```

Edit the generated file `app/Http/Middleware/CorsMiddleware.php`:

```php
<?php

namespace App\Http\Middleware;

class CorsMiddleware
{
    public function handle($request)
    {
        // Allow all origins — update to specific origin in production
        header("Access-Control-Allow-Origin: *");

        // Allow common methods and headers
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

        // Handle preflight request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        return $request;
    }
}
```

> 💡 You can restrict origins by replacing `*` with a domain like `https://myfrontend.com`.

---

## 🧩 Step 2: Apply Middleware to Routes

You can apply CORS middleware to specific routes or groups:

### Per Route

```php
Route::get('/api/data', 'ApiController@index')->middleware('CorsMiddleware');
```

### Grouped

```php
Route::group(['middleware' => 'CorsMiddleware'], function () {
    Route::get('/api/user', 'ApiController@user');
    Route::post('/api/save', 'ApiController@store');
});
```

---

## 🧪 Step 3: Test with Angular or Vue.js

In your frontend, call the API like:

```javascript
fetch('http://localhost:8000/api/user', {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

Check browser dev tools → Network tab → look for `Access-Control-Allow-Origin` header in the response.

---

## ✅ Production Tips

- Do **not** use `*` in production. Always whitelist trusted domains.
- If you're using cookies or authentication headers, also enable:

```php
header('Access-Control-Allow-Credentials: true');
```

- Consider creating a whitelist-based dynamic origin check.

---

## 🔐 Example with Restricted Origin

```php
$allowedOrigins = ['https://myfrontend.com'];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
}
```

---

## 🎉 You’re Done!

Your MarwaPHP backend can now safely serve APIs to your Vue.js, Angular, React, or mobile applications with proper CORS handling.

