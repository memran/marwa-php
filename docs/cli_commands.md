# 🧰 CLI Commands in MarwaPHP

MarwaPHP includes a built-in command-line interface powered by Symfony Console. It helps you perform routine tasks such as migrations, serving the app, generating files, and more.

---

## 🚀 Running the CLI

All commands are executed via the `marwa` entry point.

```bash
php marwa
```

This will display a list of all available commands.

---

## 📋 Commonly Used Commands

### 🔧 Migrations

```bash
php marwa migrate:init     # Initialize migration system (creates migrations table)
php marwa migrate          # Run all pending migrations
php marwa migrate:rollback # Rollback the last batch of migrations
php marwa make:migration   # Create a new migration file
```

### 🌱 Seeders

```bash
php marwa db:seed              # Run all seeders
php marwa db:seed --class=Foo  # Run specific seeder class
php marwa make:seeder          # Create a new seeder class
```

### 🧪 Testing

```bash
php marwa test   # Run tests (if integrated with PHPUnit)
```

### 🛠 Make Generators

```bash
php marwa make:controller MyController
php marwa make:model User
php marwa make:middleware AuthMiddleware
php marwa make:event UserRegistered
```

### 🌐 HTTP Server (Swoole)

```bash
php marwa http:serve
```

> ℹ️ Make sure Swoole is installed and enabled in your PHP environment.

---

## ➕ Adding Custom Commands

You can create custom commands by extending the `Symfony\Component\Console\Command\Command` class and registering it inside your app.

---

> 📝 To explore all available CLI commands, simply run `php marwa` in your terminal.
