# 🚀 Getting Started with MarwaPHP

MarwaPHP is a modern, lightweight PHP micro-framework inspired by Laravel. It follows PSR standards and provides features like routing, event handling, migrations, configuration, queues, and a Twig-based templating engine.

---

## 🧰 Installation

1. **Clone the Repository**

```bash
git clone https://github.com/memran/marwa-php.git your-project
cd your-project
```

2. **Install Dependencies**

```bash
composer install
```

3. **Set Up Environment File**

```bash
cp .env.example .env
```

Edit `.env` as needed for database, mail, and app config.

4. **Run Migrations**

```bash
php marwa migrate:init
php marwa migrate
```

5. **Start Development Server**

You can use Apache/Nginx or start the built-in development server with:

```bash
php marwa http:serve
```

---

## ⚙ Configuration

Configuration files are located in the `config/` directory. Use the `Config` facade or helper function to access them:

```php
use Marwa\Application\Facades\Config;

// Load a config file
$config = Config::load('app.php');
```

Most app settings, database connections, session drivers, mail setup, and custom settings can be managed via config files or `.env`.

---

## 🗂 Directory Structure

```text
app/            # Controllers, Models, Middlewares
config/         # Application configuration
routes/         # Route definitions (web.php, api.php)
resources/      # Views, Twig templates, frontend assets
database/       # Migrations and seeds
public/         # Entry point (index.php), assets
storage/        # Logs, sessions, compiled views
docs/           # Project documentation
```

---

## 🎨 Frontend

MarwaPHP uses Twig as the default view engine.

Example view rendering:

```php
return view('home', ['title' => 'Welcome']);
```

Views are stored in `resources/views/`. You can use template inheritance and all Twig features. Static assets (CSS/JS/images) go in `public/`.

Use the `asset()` helper to generate public URLs:

```php
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
```

---

## 🚀 Deployment

To deploy MarwaPHP to production:

1. Set document root to the `public/` directory
2. Run `composer install --no-dev`
3. Set `APP_DEBUG=false` in `.env`
4. Ensure proper file permissions for `storage/` and `bootstrap/cache/`
5. Run migrations and seeders
6. (Optional) Start the Swoole HTTP server:
   ```bash
   php marwa http:serve
   ```

---
