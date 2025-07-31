# 🔐 User Roles & Permissions in MarwaPHP

This tutorial explains how to implement a basic **Roles & Permissions** system in MarwaPHP using middleware and policies. We'll define two user types — `admin` and `user` — and restrict access to specific features accordingly.

---

## 🎭 Step 1: Define Roles in the Database

Add a `role` column in your `users` table.

### Migration Example

```php
Builder::create('users', function($table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->string('role')->default('user'); // 'admin' or 'user'
    $table->timestamps();
});
```

---

## 👤 Step 2: Set User Roles on Registration or Seeder

```php
User::create([
    'name' => 'Super Admin',
    'email' => 'admin@site.com',
    'password' => password_hash('secret', PASSWORD_DEFAULT),
    'role' => 'admin',
]);
```

---

## 📘 Step 3: Create a UserPolicy Class

Create `app/Policies/UserPolicy.php`:

```php
namespace App\Policies;

class UserPolicy
{
    public static function isAdmin($user)
    {
        return $user->role === 'admin';
    }

    public static function isUser($user)
    {
        return $user->role === 'user';
    }
}
```

---

## 🛡 Step 4: Middleware to Enforce Policy

Create `app/Http/Middleware/AuthorizeRole.php`:

```php
namespace App\Http\Middleware;

use App\Models\User;
use App\Policies\UserPolicy;

class AuthorizeRole
{
    protected $requiredRole;

    public function __construct($role)
    {
        $this->requiredRole = $role;
    }

    public function handle($request)
    {
        $authUser = $request->user;

        if (!$authUser || $authUser->role !== $this->requiredRole) {
            return response(['error' => 'Forbidden'], 403);
        }

        return $request;
    }
}
```

To create specific middleware wrappers like `IsAdmin` or `IsUser`, extend this logic or register via middleware bindings.

---

## 🧭 Step 5: Use Policy in Routes or Controllers

### In Controller:

```php
use App\Policies\UserPolicy;

if (!UserPolicy::isAdmin($request->user)) {
    return response(['error' => 'Access Denied'], 403);
}
```

### In Routes:

```php
Route::group(['middleware' => 'IsAdmin'], function () {
    Route::get('/admin/dashboard', 'AdminController@index');
});
```

---

## ✅ Optional: Blade/View Helpers

```php
<?php if (UserPolicy::isAdmin(auth()->user())): ?>
    <a href="/admin/dashboard">Admin Panel</a>
<?php endif; ?>
```

---

## 📦 Final Notes

- You can expand to permission-based checks using `can()` or `abilities`
- Consider using a `permissions` table for scalable ACL
- Use middleware for endpoint protection, policies for business logic

---

🎉 Now you’ve got a working Role & Permission system in your MarwaPHP app!

