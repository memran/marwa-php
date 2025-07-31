# 🌱 Seeder System — MarwaPHP

The seeder system in MarwaPHP allows you to populate your database with sample, default, or test data. It's useful during development, testing, and deployment automation. MarwaPHP follows a structured approach for creating and running seeders, similar to Laravel.

---

## 📌 Why Use Seeders?

- Populate databases with consistent test data
- Load default system values (roles, configs, etc.)
- Demo environments for QA or clients
- Automate initial data setup in production

---

## 📂 File Structure

All seeders are stored in the `database/seeds/` directory:

```text
database/seeds/
├── UserSeeder.php
├── RoleSeeder.php
└── DatabaseSeeder.php
```

---

## 🧱 Creating Seeders

Use the CLI to generate a seeder:

```bash
php marwa make:seeder UserSeeder
```

This will create a stub file in `database/seeds/` like:

```php
class UserSeeder
{
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'created_at' => now(),
        ]);
    }
}
```

You may use the query builder or raw SQL inside `run()`.

---

## 🚀 Running Seeders

### Run all registered seeders:

```bash
php marwa db:seed
```

By default, this calls `DatabaseSeeder::run()` which may internally call other seeders.

### Run a specific seeder:

```bash
php marwa db:seed --class=UserSeeder
```

---

## 🔁 Calling Other Seeders

In `DatabaseSeeder` you can call multiple seeders:

```php
class DatabaseSeeder
{
    public function run()
    {
        $this->call([
            UserSeeder::class,
            RoleSeeder::class,
        ]);
    }

    protected function call(array $seeders)
    {
        foreach ($seeders as $seeder) {
            (new $seeder)->run();
        }
    }
}
```

---

## 🧪 Best Practices

- Use seeders for non-sensitive, non-unique content
- Separate concerns (e.g., `UserSeeder`, `RoleSeeder`)
- Use `faker` or data generators for realistic test content
- Always verify seeders in CI before running in production

---

## ✅ Tips

- Seeders can be used after migrations to prefill lookups and master data
- Protect against duplicate inserts (e.g., use `firstOrCreate`)
- You may wrap inserts in transactions for rollback safety in tests

---

> 🌿 MarwaPHP's seeder system enables reproducible, automated population of meaningful data across all environments.
