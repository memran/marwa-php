# 🔐 JWT Authentication Tutorial — MarwaPHP

This guide walks you through implementing JSON Web Token (JWT) authentication in a MarwaPHP application. JWT is a stateless, secure way to handle user authentication using encoded tokens.

---

## 🧰 Prerequisites

- PHP 8.0+
- MarwaPHP installed
- Database with `users` table
- Composer package: `firebase/php-jwt`

Install JWT dependency:

```bash
composer require firebase/php-jwt
```

---

## 📁 Directory Structure Overview

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
routes/
└── web.php
```

---

## 🔐 Step 1: Set Up JWT Secret Key

Generate a secret key:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Put it in your `.env` file:

```env
JWT_SECRET=your_generated_secret
```

---

## 🧾 Step 2: Create Login Route and Controller

### `routes/web.php`

```php
Route::post('/login', 'AuthController@login');
```

### `AuthController.php`

```php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController
{
    public function login()
    {
        $email = request('email');
        $password = request('password');

        $user = User::where('email', $email)->first();

        if (!$user || !password_verify($password, $user->password)) {
            return response(['error' => 'Invalid credentials'], 401);
        }

        $payload = [
            'iss' => "marwaphp",
            'sub' => $user->id,
            'email' => $user->email,
            'iat' => time(),
            'exp' => time() + (60 * 60) // 1 hour
        ];

        $jwt = JWT::encode($payload, env('JWT_SECRET'), 'HS256');

        return response(['token' => $jwt]);
    }
}
```

---

## ✅ Step 3: Protect Routes with Middleware

### Middleware Example

Create a file like `app/Http/Middleware/AuthJWT.php`:

```php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthJWT
{
    public function handle($request)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response(['error' => 'Unauthorized'], 401);
        }

        $token = substr($authHeader, 7);

        try {
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            $request->user = $decoded;
        } catch (Exception $e) {
            return response(['error' => 'Invalid token'], 401);
        }

        return $request;
    }
}
```

Register the middleware in your route:

```php
Route::get('/profile', 'UserController@profile')->middleware('AuthJWT');
```

---

## 👤 Step 4: Access Authenticated User

In your controller, access `$request->user` to get user info from token:

```php
public function profile($request)
{
    $userId = $request->user->sub;
    $user = User::find($userId);

    return response(['user' => $user]);
}
```

---

## 🔁 Optional: Token Refresh & Logout

You can implement token refresh by issuing a new token before the old one expires. For logout, since JWT is stateless, you'd typically blacklist the token manually if needed.

---

## 💡 Best Practices

- Use HTTPS in production
- Rotate secret keys periodically
- Use short expiry times and refresh tokens
- Store tokens securely on the client (e.g., `HttpOnly` cookies)

---

> 🛡 JWT in MarwaPHP allows building scalable, stateless APIs and secure authentication workflows for SPAs, mobile apps, and microservices.
