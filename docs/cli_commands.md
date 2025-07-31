# 🧰 CLI Commands in MarwaPHP

MarwaPHP features a powerful built-in CLI tool powered by Symfony Console. It helps developers automate tasks such as running migrations, generating files, launching servers, and more.

---

## 🚀 Running the CLI

All commands are run through the `marwa` entry point script:

```bash
php marwa
```

This will display a list of all available commands in your application.

---

## 📋 Core Commands

### 🔧 Migrations

```bash
php marwa migrate:init          # Initializes the migrations system
php marwa migrate               # Runs all pending migrations
php marwa migrate:rollback     # Rolls back the last batch of migrations
php marwa make:migration User  # Creates a new migration file
```

### 🌱 Seeders

```bash
php marwa db:seed                   # Runs all registered seeders
php marwa db:seed --class=UserSeed # Runs a specific seeder class
php marwa make:seeder UserSeed     # Creates a new seeder class
```

### 📂 Schema

```bash
php marwa schema:dump   # Exports current database schema
php marwa schema:sync   # Syncs schema to latest model definitions
```

---

## 🧪 Testing (if integrated)

```bash
php marwa test   # Runs all available tests
```

---

## 🛠 Generators

```bash
php marwa make:controller BlogController
php marwa make:model Post
php marwa make:middleware AuthMiddleware
php marwa make:event UserRegistered
php marwa make:command CleanupLogs
```

These help scaffold boilerplate code and follow PSR naming conventions.

---

## 🌐 Serve Application

```bash
php marwa http:serve
```

Launches the Swoole-powered HTTP server (must be installed via PECL). Great for real-time apps and high-throughput APIs.

---

## 🔧 Cache Management (future release)

```bash
php marwa config:cache     # Caches the configuration files
php marwa route:cache      # Caches routes
php marwa clear:cache      # Clears all cache (config, view, route)
```

> 📝 Some of these features may be stubbed for future development.

---

## ➕ Custom Commands

You can register your own commands by extending `Symfony\Component\Console\Command\Command` and binding them in a ServiceProvider or `app.php` config.

Example:

```php
namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;

class HelloCommand extends Command {
    protected static \$defaultName = 'hello';

    protected function execute(InputInterface \$input, OutputInterface \$output) {
        $output->writeln("Hello from MarwaPHP CLI!");
        return Command::SUCCESS;
    }
}
```

---

> 🧠 The CLI in MarwaPHP is modular and extendable — designed to keep your dev workflow efficient.
