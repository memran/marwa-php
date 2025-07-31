# 🗄️ Database Overview — MarwaPHP

MarwaPHP includes a robust yet lightweight database abstraction layer built with PDO under the hood. It supports multiple database drivers, query building, migrations, and seeding with a Laravel-like developer experience — all optimized for performance and modular applications.

---

## ⚙️ Configuration

Database settings are stored in `config/database.php` and use environment variables from `.env`.

Example `.env` entries:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=marwa_app
DB_USERNAME=root
DB_PASSWORD=secret
```

---

## 🔗 Supported Drivers

- MySQL / MariaDB
- PostgreSQL
- SQLite (in-memory or file-based)
- SQL Server (if supported via PDO)

Connection types:
- `default` — primary write connection
- `read` — read-replica(s)
- `write` — write-specific overrides

---

## 🔨 Migrations

Migrations allow developers to version control their database schema.

### Initialize migrations table:

```bash
php marwa migrate:init
```

### Run pending migrations:

```bash
php marwa migrate
```

### Rollback last batch:

```bash
php marwa migrate:rollback
```

### Create a new migration:

```bash
php marwa make:migration CreateUsersTable
```

Each migration file contains `up()` and `down()` methods for forward and reverse operations.

---

## 🧬 Schema Builder

The schema builder uses fluent methods similar to Laravel.

```php
use Marwa\Database\Schema\Builder;

Builder::create('users', function($table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamps();
});
```

Available types include: `string`, `text`, `integer`, `bigint`, `boolean`, `datetime`, `json`, etc.

Modifiers: `nullable()`, `default()`, `unique()`, `index()`, `primary()`, etc.

---

## 🌱 Seeding

Seeders allow you to populate your database with dummy or default data.

### Run all seeders:

```bash
php marwa db:seed
```

### Run specific seeder:

```bash
php marwa db:seed --class=UserSeeder
```

### Create new seeder:

```bash
php marwa make:seeder UserSeeder
```

Each seeder contains a `run()` method.

---

## 🔎 Query Builder (If Available)

While not ORM-based, MarwaPHP may include a fluent query builder.

```php
DB::table('users')->where('status', 'active')->get();
```

Alternatively, raw PDO queries:

```php
DB::raw("SELECT * FROM users WHERE status = ?", ['active']);
```

---

## 📁 Migration File Structure

Migration files are timestamped and stored in `database/migrations/`.

```text
database/migrations/
├── 2023_01_01_000001_create_users_table.php
├── 2023_01_02_000002_create_posts_table.php
```

Each file defines `up()` and `down()` methods.

---

## ✅ Best Practices

- Keep migration files versioned in Git.
- Avoid modifying old migrations once applied to production.
- Use seeders for development data, not permanent production records.
- Write raw SQL queries in `DB::raw()` when performance or control is critical.

---

> 🧠 MarwaPHP's database system is built for developers who need expressive syntax without the bulk of a full ORM.
