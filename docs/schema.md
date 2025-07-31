# 🧱 Schema Builder — MarwaPHP

The Schema Builder in MarwaPHP provides a fluent, expressive interface for defining and modifying database tables using PHP. Inspired by Laravel's schema system, it enables you to create, drop, and alter tables with clean syntax and full control.

---

## ⚙️ Getting Started

To define a schema migration, use the `Builder` class within your migration files.

Each migration contains two methods:

- `up()` — describes what happens when the migration is applied
- `down()` — describes how to rollback

---

## 📐 Creating Tables

```php
use Marwa\Database\Schema\Builder;

Builder::create('users', function($table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamps();
});
```

---

## ✏️ Modifying Tables

Add or drop columns from existing tables:

```php
Builder::table('users', function($table) {
    $table->string('profile_image')->nullable();
});
```

Drop columns:

```php
Builder::table('users', function($table) {
    $table->dropColumn('profile_image');
});
```

---

## ❌ Dropping Tables

```php
Builder::drop('users');
```

Drop if exists:

```php
Builder::dropIfExists('archive');
```

---

## 🧩 Column Types

| Type        | Description                       |
|-------------|-----------------------------------|
| `id()`      | Auto-incrementing primary key     |
| `string()`  | VARCHAR                           |
| `text()`    | TEXT                              |
| `integer()` | INT                               |
| `bigint()`  | BIGINT                            |
| `boolean()` | TINYINT(1)                        |
| `datetime()`| DATETIME                          |
| `timestamp()`| TIMESTAMP                        |
| `json()`    | JSON                              |
| `enum()`    | ENUM                              |

Example:

```php
$table->enum('status', ['active', 'inactive'])->default('active');
```

---

## 🛠 Column Modifiers

- `->nullable()`
- `->default(value)`
- `->unique()`
- `->index()`
- `->primary()`
- `->unsigned()`
- `->comment('text')`

---

## 🔑 Indexes

```php
$table->primary('id');
$table->unique('email');
$table->index(['created_at']);
```

Drop indexes:

```php
$table->dropPrimary('id');
$table->dropUnique('email');
$table->dropIndex(['created_at']);
```

---

## 🧮 Foreign Keys

```php
$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
```

Drop foreign key:

```php
$table->dropForeign('user_id');
```

---

## ⏱ Timestamps & Soft Deletes

```php
$table->timestamps();        // Adds created_at and updated_at
$table->softDeletes();       // Adds deleted_at column
```

---

## 🧾 Example: Full Migration

```php
use Marwa\Database\Schema\Builder;

class CreatePostsTable
{
    public function up()
    {
        Builder::create('posts', function($table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Builder::drop('posts');
    }
}
```

---

> 💡 The Schema Builder makes evolving your database schema seamless and consistent across environments.
