# 🚚 Migrations — MarwaPHP

Migrations in MarwaPHP offer a powerful way to version-control your database schema. Inspired by Laravel, migrations allow you to define database structure in PHP and safely apply or rollback changes across environments.

---

## 📦 Why Use Migrations?

- Share schema changes with your team
- Version control your database structure
- Sync development, staging, and production schemas
- Reproducible, testable schema evolution

---

## 🧰 Basic Commands

### Initialize the migration system

```bash
php marwa migrate:init
```

Creates the internal `migrations` table to track applied migrations.

### Run all pending migrations

```bash
php marwa migrate
```

### Rollback last batch

```bash
php marwa migrate:rollback
```

### Create a new migration file

```bash
php marwa make:migration CreateUsersTable
```

This will create a new migration file in `database/migrations/` with timestamp prefix and stubbed `up()` and `down()` methods.

---

## 📁 Migration File Structure

Each migration file contains a class with two methods:

```php
class CreateUsersTable
{
    public function up()
    {
        Builder::create('users', function($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down()
    {
        Builder::drop('users');
    }
}
```

- `up()` is executed when running `php marwa migrate`
- `down()` is executed on `php marwa migrate:rollback`

---

## 🗃 Naming Convention

Migrations are prefixed with timestamps to ensure proper order.

```text
2025_07_31_000001_create_users_table.php
2025_07_31_000002_create_posts_table.php
```

---

## 🔁 Batch Processing

Migrations are grouped into batches. When you rollback, the last batch is removed as a group. This allows coordinated rollbacks during deployment.

---

## 🚨 Rollback Strategies

You can write `down()` methods to safely undo the actions in `up()`.

Best practices:

- Always include `down()` logic
- Don't rely on `dropIfExists` in rollback unless necessary
- Rollback foreign keys before dropping tables

---

## 🧠 Tips & Best Practices

- Always commit your migrations to version control
- Run migrations on CI/CD pipelines for consistent environments
- Avoid editing previously committed migrations
- Use `php marwa migrate` before feature branches are merged
- Use seeders to fill demo/test data — not real production records

---

> 📘 Migrations in MarwaPHP give you fine-grained control over schema evolution with the safety of rollback and batch grouping.
