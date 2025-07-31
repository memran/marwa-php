# 🔐 Permission-Based Access Control with `can()` in MarwaPHP

This tutorial shows how to implement **fine-grained permission checks** using `can()`-style helpers or ability mapping, similar to Laravel’s authorization system.

---

## 🎭 Use Case

You want to restrict actions (e.g., `edit-post`, `delete-user`) based on **permissions**, not just roles. This allows more flexibility than role-based checks alone.

---

## 🧱 Step 1: Add Permissions Table (Optional but Recommended)

If you want to persist permissions in the database:

```php
Builder::create('permissions', function($table) {
    $table->id();
    $table->string('name')->unique(); // e.g., 'edit-post'
});
```

Then, create a pivot table to assign permissions to roles or users.

```php
Builder::create('permission_user', function($table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('permission_id');
});
```

---

## 👤 Step 2: Add `can()` Method to User Model

In `app/Models/User.php`:

```php
public function can($ability)
{
    // Example: hardcoded permissions
    $permissions = [
        'admin' => ['edit-post', 'delete-user', 'view-logs'],
        'editor' => ['edit-post'],
        'user' => []
    ];

    $role = $this->role;

    return in_array($ability, $permissions[$role] ?? []);
}
```

> ✅ You can extend this to fetch from DB if needed.

---

## ⚙️ Step 3: Use `can()` in Controllers

```php
if (!$request->user->can('delete-user')) {
    return response(['error' => 'Unauthorized.'], 403);
}
```

---

## 🧪 Example Use Cases

### Edit a post:

```php
if ($request->user->can('edit-post')) {
    // Allow edit
} else {
    return response(['error' => 'Permission denied.'], 403);
}
```

### Show admin panel:

```php
<?php if (auth()->user()->can('view-dashboard')): ?>
    <a href="/admin">Admin Dashboard</a>
<?php endif; ?>
```

---

## 🛡 Define Abilities with Policy Classes (Optional)

Create `UserPolicy.php`:

```php
class UserPolicy
{
    public static function canEditPost($user)
    {
        return $user->can('edit-post');
    }

    public static function canDeleteUser($user)
    {
        return $user->can('delete-user');
    }
}
```

Use in controller:

```php
use App\Policies\UserPolicy;

if (!UserPolicy::canDeleteUser($request->user)) {
    return response(['error' => 'Access denied.'], 403);
}
```

---

## 🔒 Tips for Production

- Store permissions in DB for flexibility
- Cache permissions per user/role
- Build `Gate`-like abstraction to register abilities globally

---

## ✅ Summary

| Feature | Role-based | Permission-based |
|--------|-------------|------------------|
| Coarse access control | ✅ Yes | ✅ Yes |
| Fine-grained control  | ❌ No  | ✅ Yes |
| Flexible/Scalable     | ⚠️ Limited | ✅ Preferred |

---

By using `can()` and ability-based permissions, your MarwaPHP application becomes more secure and scalable.

