# 🧬 Model Example — MarwaPHP

MarwaPHP uses an Active Record-style ORM that allows your database tables to be mapped to PHP classes. Models provide a convenient and powerful way to interact with your database through object-oriented syntax.

---

## 📦 Creating a Model

Use the CLI command to generate a model:

```bash
php marwa make:model User
```

This will generate a file like `app/Models/User.php`.

---

## 🧱 Basic Model Example

```php
namespace App\Models;

use Marwa\Database\Model\Model;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}
```

---

## 🔄 CRUD Operations

### Create a new record

```php
User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('secret'),
]);
```

### Read records

```php
$users = User::all();

$user = User::find(1);

$user = User::where('email', 'john@example.com')->first();
```

### Update a record

```php
$user = User::find(1);
$user->name = 'Jane Doe';
$user->save();
```

Or:

```php
User::where('id', 1)->update(['name' => 'Jane Doe']);
```

### Delete a record

```php
User::destroy(1);
```

Or:

```php
$user = User::find(1);
$user->delete();
```

---

## 🧩 Query Builder Integration

Models can use raw queries or builder chaining:

```php
$activeUsers = User::where('status', 'active')->orderBy('created_at', 'desc')->get();
```

---

## 🔐 Hidden & Cast Attributes

```php
protected $hidden = ['password'];

protected $casts = [
    'created_at' => 'datetime',
    'is_admin' => 'boolean',
];
```

---

## 🌐 Relationships (Planned / Optional)

Depending on implementation, you can define:

```php
public function posts()
{
    return $this->hasMany(Post::class);
}

public function role()
{
    return $this->belongsTo(Role::class);
}
```

---

## 🧪 Validation & Events (optional extensions)

You may hook into events or validations using observer classes or boot methods.

---

## ✅ Best Practices

- Always define `$fillable` to protect from mass assignment
- Use scopes or query builder methods to avoid repetition
- Keep logic out of controllers — delegate to models
- Return collections or models directly for REST APIs

---

> 🧬 Models in MarwaPHP act as the core of your data layer — they handle queries, represent entities, and simplify data access.

