# 🔐 MarwaPHP Authentication System Tutorial

This tutorial walks you through implementing a basic authentication system using MarwaPHP. We’ll cover:

- User registration
- Login/logout
- Session-based authentication
- Middleware protection
- Password hashing
- Basic profile view

---

## 📁 Step 1: User Model

```php
namespace App\Models;

use Marwa\MVC\Model\Model;

class User extends Model
{
    protected $table = "users";
    protected $fillable = ["name", "email", "password"];
    protected $hidden = ["password"];
}
```

---

## 🏗 Step 2: Database Migration

```php
Builder::create('users', function($table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->timestamps();
});
```

Run the migration:

```bash
php cli migrate
```

---

## 📝 Step 3: Registration Controller

```php
public function register(Request $request)
{
    $data = $request->only(['name', 'email', 'password']);
    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

    $user = User::create($data);

    session()->set('user_id', $user->id);
    return redirect('/dashboard');
}
```

---

## 🔐 Step 4: Login Controller

```php
public function login(Request $request)
{
    $user = User::where('email', $request->get('email'))->first();

    if ($user && password_verify($request->get('password'), $user->password)) {
        session()->set('user_id', $user->id);
        return redirect('/dashboard');
    }

    return redirect('/login')->with('error', 'Invalid credentials');
}
```

---

## 🚪 Step 5: Logout

```php
public function logout()
{
    session()->remove('user_id');
    return redirect('/');
}
```

---

## 🔒 Step 6: Auth Middleware

```php
namespace App\Middleware;

use Marwa\MVC\Middleware\Middleware;

class AuthMiddleware extends Middleware
{
    public function handle($request)
    {
        if (!session()->has('user_id')) {
            return redirect('/login');
        }

        $request->user = User::find(session()->get('user_id'));
    }
}
```

Register the middleware in `config/middleware.php`.

---

## 👤 Step 7: Profile Route and View

```php
Route::get('/profile', function(Request $request) {
    $user = User::find(session()->get('user_id'));
    return view('profile.twig', ['user' => $user]);
});
```

---

## ✅ Tips

- Always hash passwords.
- Use CSRF protection in forms.
- Use Flash messages for success/errors.
- Use Mail/Events for activation if needed.

---

## 📦 Summary

| Task          | Location/Tool         |
|---------------|------------------------|
| User Model    | `App\Models\User`    |
| Migration     | `php cli migrate`      |
| Session       | `session()->set()`     |
| Middleware    | `AuthMiddleware`       |
| Views         | `Twig` templates       |

---

This provides a complete, minimal, and secure session-based auth flow for MarwaPHP.

