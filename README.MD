# MarwaPHP Starter

[![Composer](https://img.shields.io/packagist/v/memran/marwa-php?label=composer)](https://packagist.org/packages/memran/marwa-php)
[![Downloads](https://img.shields.io/packagist/dt/memran/marwa-php?label=downloads)](https://packagist.org/packages/memran/marwa-php)
[![PHP](https://img.shields.io/packagist/dependency-v/memran/marwa-php/php?label=php)](https://packagist.org/packages/memran/marwa-php)
[![PHPUnit](https://img.shields.io/github/actions/workflow/status/memran/marwa-php/ci.yml?branch=main&label=phpunit)](https://github.com/memran/marwa-php/actions/workflows/ci.yml)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/memran/marwa-php/ci.yml?branch=main&label=phpstan)](https://github.com/memran/marwa-php/actions/workflows/ci.yml)

MarwaPHP Starter is a `composer create-project` application built on top of `memran/marwa-framework`.
It is a thin, production-ready starting point for building real Marwa apps without inheriting framework internals into the app layer.

## Who Should Use It

- developers starting a new Marwa application
- teams that want a clean starter with theme support and route examples
- anyone who wants framework-native defaults without a lot of scaffolding noise

## Install

```bash
composer create-project memran/marwa-php my-app
cd my-app
php marwa migrate
php marwa module:migrate
php marwa module:seed
php -S localhost:8000 -t public/
```

The post-create script generates `.env` from `.env.example` and builds assets when Node.js is available.

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js 20+ for Tailwind development and production builds
- Optional: Docker and Docker Compose

## Docker Deployment

The starter provides production-ready Docker configurations for containerized deployment. Both setups include PHP-FPM, MariaDB, and a web server, with an automated entrypoint that handles migrations, seeders, queue workers, and the scheduler.

### Available Stacks

| Stack | Compose File | Web Server | Use Case |
|-------|--------------|------------|---------|
| Standard | `docker/docker-compose.yml` | Nginx | Production-like setup |
| Alternative | `docker/docker-compose.fpm.yml` | Caddy | Simpler config, auto-TLS |

### Quick Start

**1. Set up environment:**

```bash
cd docker
cp docker.env.example docker.env
```

Edit `docker.env` with your preferred credentials (the example contains placeholders).

**2. Choose your stack and start:**

```bash
# Option A: Nginx + PHP-FPM
docker compose -f docker-compose.yml up -d --build

# Option B: Caddy + PHP-FPM
docker compose -f docker-compose.fpm.yml up -d --build
```

**3. Access the application:**

- Nginx stack: http://localhost:8080
- Caddy stack: http://localhost:80

### What Happens on Startup

The app container runs an entrypoint script that automates the entire bootstrap:

1. **Waits for database** - Ensures MariaDB is healthy before proceeding
2. **Runs migrations** - Executes `php marwa migrate --no-interaction`
3. **Seeds database** - Executes `php marwa db:seed --no-interaction`
4. **Starts queue worker** - Launches `php marwa queue:work --daemon` in background
5. **Starts scheduler** - Launches `php marwa schedule:work` in background
6. **Sets permissions** - Configures storage directory permissions
7. **Launches PHP-FPM** - Starts the PHP process manager

### Configuration

**Database Connection (App):**

The app reads standard `DB_*` environment variables:

```env
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_NAME=marwa
DB_USER=marwa
DB_PASSWORD=super-secret
```

**MariaDB Initialization:**

The MariaDB container uses `MARIADB_*` variables for first-boot setup:

```env
MARIADB_ROOT_PASSWORD=super-secret
MARIADB_DATABASE=marwa
MARIADB_USER=marwa
MARIADB_PASSWORD=super-secret
```

**Admin Credentials:**

Set the bootstrap admin account in `docker.env`:

```env
ADMIN_BOOTSTRAP_EMAIL=admin@example.com
ADMIN_BOOTSTRAP_PASSWORD=YourSecurePassword123!
```

### Common Operations

**View logs:**
```bash
docker compose -f docker-compose.yml logs -f
```

**Restart services:**
```bash
docker compose -f docker-compose.yml restart
```

**Stop the stack:**
```bash
docker compose -f docker-compose.yml down
```

**Reset database (if you change credentials):**
```bash
docker compose -f docker-compose.yml down -v
```

**Rebuild after changes:**
```bash
docker compose -f docker-compose.yml up -d --build
```

### Development vs Production

- **Development:** Set `APP_ENV=local` and `APP_DEBUG=1` in `docker.env`
- **Production:** Set `APP_ENV=production` and `APP_DEBUG=0` in `docker.env`
- **Staging:** Set `APP_ENV=staging` in `docker.env`

The Database Manager module is disabled by default in production/staging. Enable it with `DATABASE_MANAGER_ENABLED=1` only for controlled environments.
The Database Backup module lets admins schedule backups, store archives on a configured disk, and restore from stored or uploaded ZIP/TAR archives. Restore is destructive and replaces all current data.
The Security Risk report is available to admin users at `/admin/security/risk` and surfaces the framework risk log with a time-window filter and the latest signal data.
The security risk log can be pruned with `php marwa security:risk-prune`; the starter also schedules that command to run daily at midnight using the configured retention window.
The public welcome page now includes an admin access link, demo bootstrap credentials, and a short overview of the admin interface features.

The queue backend is also database-first now. The starter uses the global queue contract, stores jobs in `queue_jobs`, and exposes a `Queue` admin module for job status and retries. To process queued jobs from cron or a supervisor, run:

```bash
php marwa queue:work --max-time=60 --sleep=1
```

If you want the file queue back for local experiments, set `QUEUE_DRIVER=file` in `.env`.

To run module-local seeders manually, use `php marwa module:seed`.
If you want to add a new module using the same conventions as `Users`, see [docs/module-authoring.md](docs/module-authoring.md).

Create that file from [docker/docker.env.example](docker/docker.env.example) before starting the stack. The example file contains placeholder credentials only.

```bash
cp docker/docker.env.example docker/docker.env
```

Run the default stack with:

```bash
docker compose -f docker/docker-compose.yml up --build
```

If you change the MariaDB credentials after the first boot, reset the database volume so the container can initialize with the new values:

```bash
docker compose -f docker/docker-compose.yml down -v
```

## Quick Start

```bash
composer install
cp .env.example .env
php marwa migrate
php marwa module:migrate
php marwa module:seed
php -S localhost:8000 -t public/
```

For Docker:

```bash
cd docker
cp docker.env.example docker.env
docker compose -f docker-compose.yml up --build
```

For local frontend assets:

```bash
npm install
npm run dev
```

To rebuild the admin theme assets while developing:

```bash
npm run css:dev:admin
```

## Running the App

- `php -S localhost:8000 -t public/` runs the HTTP app locally
- `composer test` runs the PHPUnit suite
- `composer analyse` runs PHPStan
- `composer lint` checks PHP syntax
- `composer ci` runs the local validation chain
- `php marwa db:check` checks the active database connection and prints the result
- `npm run build` compiles production CSS into `public/assets/css/app.css` and `public/themes/admin/assets/css/app.css`
- `npm run css:build:admin` compiles only the admin theme stylesheet into `public/themes/admin/assets/css/app.css`
- The admin layouts load `public/themes/admin/css/app.css`, which forwards to the compiled admin stylesheet above

## Developer Tutorial

### Project Structure

```
app/
├── Http/
│   ├── Controllers/      # Thin controllers
│   └── Middleware/       # App-specific middleware (theme, CSRF, etc.)
├── Support/             # App-specific services (PermissionGate, etc.)
└── Providers/           # Service providers (AdminNavigation, etc.)
modules/                # Self-contained feature modules
├── Auth/              # Authentication & authorization
├── Users/             # User management CRUD
├── Activity/          # Activity logging
├── BackgroundJobs/    # Scheduler UI
├── Queue/             # Queue management UI
├── Dashboard/         # Admin dashboard
├── Settings/          # Settings management
└── Notifications/     # Notification system
config/                 # App configuration overrides
resources/
├── views/
│   ├── components/   # Reusable Twig components
│   └── themes/       # Frontend (default) and admin themes
└── lang/             # Translation files (if used)
routes/                 # HTTP route definitions
database/
├── migrations/        # Shared migrations (queue, scheduler tables)
└── sqlite/           # SQLite database (gitignored)
```

### Creating a New Module

Follow the `Users` module as a reference. Here's the minimal structure:

```bash
# Create module structure
mkdir -p modules/Blog/{database/migrations,database/seeders,Http/Controllers,resources/views,routes}
```

**1. Create a manifest (`modules/Blog/manifest.php`):**

```php
<?php

declare(strict_types=1);

return [
    'name' => 'Blog Module',
    'slug' => 'blog',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Blog\BlogServiceProvider::class,
    ],
    'requires' => [],
    'paths' => [
        'views' => 'resources/views',
    ],
    'permissions' => [
        'blog.view' => 'View Blog',
        'blog.create' => 'Create Blog Posts',
        'blog.edit' => 'Edit Blog Posts',
        'blog.delete' => 'Delete Blog Posts',
    ],
    'menu' => [
        'section' => 'Content',
        'label' => 'Blog',
        'route' => 'admin.blog.index',
        'icon' => 'pen-tool',
        'permissions' => ['blog.view'],
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'migrations' => [
        'database/migrations/2026_05_03_000001_create_posts_table.php',
    ],
    'seeders' => [
        'database/seeders/BlogPermissionsSeeder.php',
    ],
];
```

**2. Create a migration (`modules/Blog/database/migrations/2026_05_03_000001_create_posts_table.php`):**

```php
<?php

declare(strict_types=1);

use Marwa\DB\Schema\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $this->schema->createTable('posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->integer('user_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema->dropTable('posts');
    }
};
```

**3. Create a model (`modules/Blog/Models/Post.php`):**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Models;

use App\Modules\Users\Models\User;
use Marwa\DB\Eloquent\Model;

final class Post extends Model
{
    protected string $table = 'posts';

    protected array $fillable = ['title', 'body', 'user_id'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

**4. Create a controller (`modules/Blog/Http/Controllers/PostController.php`):**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Http\Controllers;

use App\Modules\Blog\Models\Post;
use Marwa\Framework\Controllers\Controller;
use Marwa\Framework\Views\View;

final class PostController extends Controller
{
    public function index(): string
    {
        $posts = Post::query()->orderBy('created_at', 'DESC')->paginate(10);

        return view('blog::index', ['posts' => $posts]);
    }

    public function create(): string
    {
        return view('blog::create');
    }

    public function store(): void
    {
        $data = validate_request([
            'title' => 'required|min:3|max:255',
            'body' => 'required|min:10',
        ]);

        Post::query()->create([
            'title' => $data['title'],
            'body' => $data['body'],
            'user_id' => auth()->id(),
        ]);

        redirect('/admin/blog')->with('success', 'Post created successfully');
    }
}
```

**5. Define routes (`modules/Blog/routes/http.php`):**

```php
<?php

declare(strict_types=1);

use App\Modules\Blog\Http\Controllers\PostController;
use Marwa\Framework\Facades\Route;

Route::group(['prefix' => 'admin/blog', 'middleware' => ['auth', 'can:blog.view']], function () {
    Route::get('/', [PostController::class, 'index'])->name('admin.blog.index');
    Route::get('/create', [PostController::class, 'create'])->name('admin.blog.create');
    Route::post('/', [PostController::class, 'store'])->name('admin.blog.store');
});
```

**6. Create views (`modules/Blog/resources/views/index.twig`):**

```twig
{% extends 'admin::layouts/main.twig' %}

{% block content %}
<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Blog Posts</h1>
    <a href="{{ route('admin.blog.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded">New Post</a>

    <div class="mt-6 space-y-4">
        {% for post in posts.data %}
            <div class="border p-4 rounded">
                <h2 class="text-xl font-semibold">{{ post.title }}</h2>
                <p class="text-gray-600">{{ post.body|slice(0, 200) }}...</p>
            </div>
        {% else %}
            <p>No posts yet.</p>
        {% endfor %}
    </div>

    {{ pagination(posts) }}
</div>
{% endblock %}
```

**7. Create a service provider (`modules/Blog/BlogServiceProvider.php`):**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog;

use Marwa\Module\Contracts\ModuleServiceProviderInterface;
use Marwa\Framework\Application;

final class BlogServiceProvider implements ModuleServiceProviderInterface
{
    public function setContainer(\League\Container\Container $container): void
    {
    }

    public function register($app): void
    {
    }

    public function boot($app): void
    {
    }
}
```

**8. Run migrations and seeders:**

```bash
php marwa module:migrate
php marwa module:seed
```

### Working with Permissions

The starter uses a simple permission system. Check permissions in:

**Controllers:**
```php
if (!auth()->user()->hasPermission('blog.create')) {
    abort(403);
}
```

**Twig templates:**
```twig
{% if can('blog.edit') %}
    <a href="{{ route('admin.blog.edit', {id: post.id}) }}">Edit</a>
{% endif %}
```

**Route middleware:**
```php
Route::get('/protected', fn() => 'Hello')->middleware('can:blog.view');
```

### Working with Themes

The starter supports multiple themes:

- **Frontend**: Configured via `FRONTEND_THEME` in `.env` (default: `default`)
- **Admin**: Configured via `ADMIN_THEME` in `.env` (default: `admin`)

Theme views are in `resources/views/themes/{theme_name}/views/`.

To create a new theme:

```bash
mkdir -p resources/views/themes/my-theme/views
cp -r resources/views/themes/default/views/* resources/views/themes/my-theme/views/
```

### Scheduled Tasks

To add a scheduled task, register it in a module's service provider:

```php
use Marwa\Framework\Scheduling\Task;

public function boot($app): void
{
    $app->registerTask(
        (new Task('blog:cleanup', function () {
            // Cleanup old drafts
            return 'Cleanup complete';
        }))
            ->description('Remove old blog drafts')
            ->daily()
    );
}
```

Available schedules: `->everyMinute()`, `->hourly()`, `->daily()`, `->weekly()`, `->monthly()`, `->everySecond()` (for testing).

### Queue Jobs

To dispatch a job:

```php
use App\Modules\Queue\Support\Queue;

Queue::push(function () {
    // Job logic here
    \Marwa\DB\Facades\DB::table('posts')->where('status', 'draft')->delete();
}, 'default');
```

Monitor jobs at `/admin/queue` (admin only).

### Activity Logging

Log activities directly in your module:

```php
use App\Modules\Activity\Support\ActivityRecorder;

ActivityRecorder::record([
    'user_id' => auth()->id(),
    'action' => 'created_post',
    'description' => 'Created blog post: ' . $post->title,
    'module' => 'Blog',
]);
```

View activities at `/admin/activity`.

### Database Seeding

Create a seeder in your module's `database/seeders/` folder:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Database\Seeders;

use App\Modules\Blog\Models\Post;
use Marwa\DB\Seeder\Seeder;

final class BlogPostsSeeder implements Seeder
{
    public function run(): void
    {
        for ($i = 0; $i < 10; $i++) {
            Post::query()->create([
                'title' => 'Sample Post ' . ($i + 1),
                'body' => 'This is a sample blog post body...',
                'user_id' => 1,
            ]);
        }
    }
}
```

Register in `manifest.php` and run:
```bash
php marwa module:seed
```

### Testing

Create tests in the `tests/` directory:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BlogPostTest extends TestCase
{
    public function test_post_creation(): void
    {
        // Test logic here
        $this->assertTrue(true);
    }
}
```

Run tests:
```bash
composer test              # Run all tests
composer analyse           # Run PHPStan (level 6)
composer ci                 # Run full validation chain
```

### Best Practices

1. **Keep controllers thin** - Put business logic in services or models
2. **Use framework helpers** - `view()`, `config()`, `auth()`, `validate_request()`
3. **Follow PSR-12** - Run `composer lint` before committing
4. **Type everything** - Use strict_types, typed properties, return types
5. **Module isolation** - Keep modules self-contained and decoupled
6. **No raw SQL** - Use ORM or Query Builder from `marwa-db`
7. **Test behavior** - Not implementation details
8. **Security first** - Validate and sanitize all user input

### Module Authoring Guide

For detailed module conventions, see [docs/module-authoring.md](docs/module-authoring.md).

### Common Commands

```bash
# Development
php -S localhost:8000 -t public/     # Start local server
npm run dev                          # Start Vite dev server for assets

# Database
php marwa migrate                     # Run all migrations
php marwa module:migrate              # Run module migrations
php marwa db:seed                     # Run seeders
php marwa module:seed                 # Run module seeders

# Queue & Scheduler
php marwa queue:work --daemon       # Start queue worker
php marwa schedule:run               # Run scheduled tasks

# Docker
cd docker && docker compose up -d   # Start Docker stack
docker compose logs -f               # View logs

# Code quality
composer test                        # Run PHPUnit
composer analyse                     # Run PHPStan
composer lint                        # Check PHP syntax
composer ci                          # Full validation
```

For module-specific development, see the [Module Authoring Guide](docs/module-authoring.md).
