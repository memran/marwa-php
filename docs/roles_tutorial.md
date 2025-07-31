# 🛡 Role-Based Access Control (RBAC) in MarwaPHP

This tutorial demonstrates how to implement **role-based authorization** in MarwaPHP. Roles are used to group users by their level of access (e.g., `admin`, `editor`, `viewer`).

---

## 🎭 Step 1: Add `role` to Users Table

Modify your users table migration to include a role field:

```php
Builder::create('users', function($table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->string('role')->default('user'); // roles: 'admin', 'editor', 'user'
    $table->timestamps();
});
```

---

## 👤 Step 2: Set Roles in Seeder or Registration

```php
User::create([
    'name' => 'Admin User',
    'email' => 'admin@site.com',
    'password' => password_hash('secret', PASSWORD_DEFAULT),
    'role' => 'admin',
]);
```

---

## 🧠 Step 3: Add `hasRole()` to User Model

In `app/Models/User.php`:

```php
public function hasRole($role)
{
    return $this->role === $role;
}

public function hasAnyRole($roles)
{
    return in_array($this->role, (array) $roles);
}
```

---

## ✅ Step 4: Check Role in Controller

```php
if (!$request->user->hasRole('admin')) {
    return response(['error' => 'Access Denied'], 403);
}
```

Or for multiple roles:

```php
if (!$request->user->hasAnyRole(['admin', 'editor'])) {
    return response(['error' => 'Unauthorized'], 403);
}
```

---

## 🧭 Step 5: Role-Based Middleware (Optional)

You can write a reusable middleware class:

```php
namespace App\Http\Middleware;

class RoleMiddleware
{
    protected $role;

    public function __construct($role)
    {
        $this->role = $role;
    }

    public function handle($request)
    {
        if (!$request->user || $request->user->role !== $this->role) {
            return response(['error' => 'Forbidden'], 403);
        }
        return $request;
    }
}
```

Register `IsAdmin`, `IsEditor`, etc. via route middleware or globally.

---

## 💡 Example Usage in Views

```php
<?php if (auth()->user()->hasRole('admin')): ?>
    <a href="/admin">Admin Panel</a>
<?php endif; ?>
```

---

## 🧪 Use in Routes

```php
Route::group(['middleware' => 'IsAdmin'], function () {
    Route::get('/admin/dashboard', 'AdminController@index');
});
```

---

## 🧩 Summary

| Role       | Access Area                |
|------------|----------------------------|
| admin      | Full control               |
| editor     | Manage content             |
| user       | Basic user operations only |

---

✅ Role-based access control is simple to implement and effective for small-to-medium-sized applications.

For more complex scenarios, consider combining roles with permission-based access (`can()`) described in a separate tutorial.
