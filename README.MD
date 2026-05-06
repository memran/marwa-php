# MarwaPHP Starter

[![Composer](https://img.shields.io/packagist/v/memran/marwa-php?label=composer)](https://packagist.org/packages/memran/marwa-php)
[![Downloads](https://img.shields.io/packagist/dt/memran/marwa-php?label=downloads)](https://packagist.org/packages/memran/marwa-php)
[![PHP](https://img.shields.io/packagist/dependency-v/memran/marwa-php/php?label=php)](https://packagist.org/packages/memran/marwa-php)
[![PHPUnit](https://img.shields.io/github/actions/workflow/status/memran/marwa-php/ci.yml?branch=main&label=phpunit)](https://github.com/memran/marwa-php/actions/workflows/ci.yml)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/memran/marwa-php/ci.yml?branch=main&label=phpstan)](https://github.com/memran/marwa-php/actions/workflows/ci.yml)

MarwaPHP Starter is a `composer create-project` application built on top of `memran/marwa-framework`.
It is a thin, production-ready starting point for building real Marwa apps without inheriting framework internals into the app layer.

---

## 1. Quick Start

```bash
composer create-project memran/marwa-php my-app
cd my-app
php marwa migrate
php marwa module:migrate
php marwa module:seed
php -S localhost:8000 -t public/
```

Access at http://localhost:8000

---

## 2. Requirements

- PHP 8.2+
- Composer
- Node.js 20+ (for Tailwind CSS builds)
- SQLite (default) or MySQL/MariaDB

---

## 3. Running the Application

### Local Development

```bash
# Install dependencies
composer install
cp .env.example .env

# Setup database
php marwa migrate
php marwa module:migrate
php marwa module:seed

# Start local server
php -S localhost:8000 -t public/
```

### Frontend Assets

```bash
# Install Node dependencies
npm install

# Development (with hot reload)
npm run dev

# Production build
npm run build

# Build admin theme only
npm run css:build:admin
```

### Common Commands

| Command | Description |
|---------|-------------|
| `php -S localhost:8000 -t public/` | Start local development server |
| `php marwa migrate` | Run framework migrations |
| `php marwa module:migrate` | Run module migrations |
| `php marwa module:seed` | Seed module data |
| `php marwa queue:work --daemon` | Start queue worker |
| `php marwa schedule:run` | Run scheduled tasks |
| `php marwa db:check` | Check database connection |

### Code Quality

```bash
composer test       # Run PHPUnit
composer analyse    # Run PHPStan (level 6)
composer lint        # Check PHP syntax
composer ci          # Full validation chain
```

---

## 4. Project Structure

```
app/
├── Http/
│   ├── Controllers/    # Thin controllers
│   └── Middleware/     # App-specific middleware
├── Providers/          # Service providers
└── Commands/          # Console commands
modules/               # Feature modules
├── Auth/              # Authentication
├── Users/             # User management
├── Activity/          # Activity logging
├── Settings/          # Settings management
├── BackgroundJobs/   # Scheduler UI
├── Queue/             # Queue management
├── Dashboard/         # Admin dashboard
└── Notifications/     # Notification system
config/                # App configuration
resources/
├── views/
│   ├── components/    # Shared Twig components
│   └── themes/        # Theme views (default, admin)
└── css/               # Source stylesheets
routes/                # HTTP routes
database/
├── migrations/        # Shared migrations
└── sqlite/           # SQLite database
tests/                 # PHPUnit tests
```

---

## 5. Creating a New Module

Create a self-contained module following the `Users` module structure:

```bash
mkdir -p modules/Blog/{database/migrations,database/seeders,Http/Controllers,Models,resources/views,routes}
```

### Module Manifest

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
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'migrations' => [
        'database/migrations/2026_05_03_000001_create_posts_table.php',
    ],
    'seeders' => [
        'database/seeders/BlogPermissionsSeeder.php',
    ],
    'permissions' => [
        'blog.view' => 'View Blog',
        'blog.create' => 'Create Posts',
        'blog.edit' => 'Edit Posts',
        'blog.delete' => 'Delete Posts',
    ],
    'menu' => [
        'section' => 'Content',
        'label' => 'Blog',
        'route' => 'admin.blog.index',
        'icon' => 'pen-tool',
        'permissions' => ['blog.view'],
    ],
];
```

### Migration Example

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

### Model Example

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

### Controller Example

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Http\Controllers;

use App\Modules\Blog\Models\Post;
use Marwa\Framework\Controllers\Controller;

final class PostController extends Controller
{
    public function index(): string
    {
        $posts = Post::query()->orderBy('created_at', 'DESC')->paginate(10);
        return view('blog::index', ['posts' => $posts]);
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

        redirect('/admin/blog')->with('success', 'Post created');
    }
}
```

### Routes Example

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

### Run Module Setup

```bash
php marwa module:migrate
php marwa module:seed
```

For detailed conventions, see [docs/module-authoring.md](docs/module-authoring.md).

---

## 6. Working with Permissions

### Check in Controller

```php
if (!auth()->user()->hasPermission('blog.create')) {
    abort(403);
}
```

### Check in Template

```twig
{% if can('blog.edit') %}
    <a href="{{ route('admin.blog.edit', {id: post.id}) }}">Edit</a>
{% endif %}
```

### Protect Routes

```php
Route::get('/protected', fn() => 'Hello')->middleware('can:blog.view');
```

---

## 7. Scheduled Tasks

Register in a service provider:

```php
use Marwa\Framework\Scheduling\Task;

public function boot($app): void
{
    $app->registerTask(
        (new Task('blog:cleanup', function () {
            return 'Cleanup complete';
        }))
            ->description('Remove old drafts')
            ->daily()
    );
}
```

Available schedules: `everyMinute()`, `hourly()`, `daily()`, `weekly()`, `monthly()`.

Run scheduled tasks:

```bash
php marwa schedule:run
```

---

## 8. Queue Jobs

### Dispatch a Job

```php
use App\Modules\Queue\Support\Queue;

Queue::push(function () {
    // Job logic
    \Marwa\DB\Facades\DB::table('posts')->where('status', 'draft')->delete();
}, 'default');
```

### Process Jobs

```bash
php marwa queue:work --daemon
```

Monitor at `/admin/queue` (admin only).

---

## 9. Activity Logging

```php
use App\Modules\Activity\Support\ActivityRecorder;

ActivityRecorder::record([
    'user_id' => auth()->id(),
    'action' => 'created_post',
    'description' => 'Created: ' . $post->title,
    'module' => 'Blog',
]);
```

View at `/admin/activity`.

---

## 10. Themes

- **Frontend**: Set `FRONTEND_THEME` in `.env` (default: `default`)
- **Admin**: Set `ADMIN_THEME` in `.env` (default: `admin`)

Theme views: `resources/views/themes/{theme_name}/views/`

### Create a New Theme

```bash
mkdir -p resources/views/themes/my-theme/views
cp -r resources/views/themes/default/views/* resources/views/themes/my-theme/views/
```

---

## 11. Docker Deployment

### Quick Start

```bash
cd docker
cp docker.env.example docker.env
docker compose -f docker-compose.yml up -d --build
```

Access at http://localhost:8080

### Available Stacks

| Stack | Compose File | Port |
|-------|--------------|------|
| Nginx + PHP-FPM | `docker-compose.yml` | 8080 |
| Caddy + PHP-FPM | `docker-compose.fpm.yml` | 80 |

### Environment Variables

```env
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_NAME=marwa
DB_USER=marwa
DB_PASSWORD=secret

ADMIN_BOOTSTRAP_EMAIL=admin@example.com
ADMIN_BOOTSTRAP_PASSWORD=SecurePassword123!

APP_ENV=production
APP_DEBUG=0
```

### Common Operations

```bash
# View logs
docker compose -f docker-compose.yml logs -f

# Restart
docker compose -f docker-compose.yml restart

# Stop
docker compose -f docker-compose.yml down

# Reset database
docker compose -f docker-compose.yml down -v

# Rebuild
docker compose -f docker-compose.yml up -d --build
```

### What Happens on Startup

The entrypoint runs automatically:

1. Waits for database
2. Runs `php marwa migrate`
3. Runs `php marwa module:migrate`
4. Runs `php marwa module:seed`
5. Starts queue worker (`php marwa queue:work --daemon`)
6. Starts scheduler (`php marwa schedule:run`)
7. Launches PHP-FPM

---

## 12. Configuration

### Database

Default is SQLite. For MySQL/MariaDB:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=marwa
DB_USER=marwa
DB_PASSWORD=secret
```

### Queue

Default is database queue. For file-based queue:

```env
QUEUE_DRIVER=file
```

### Environment

```env
APP_ENV=local|production|staging
APP_DEBUG=1|0
```

---

## 13. Testing

### Create a Test

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BlogPostTest extends TestCase
{
    public function test_post_creation(): void
    {
        $this->assertTrue(true);
    }
}
```

### Run Tests

```bash
composer test       # Run all tests
composer analyse    # Run PHPStan
composer ci         # Full validation
```

---

## 14. Best Practices

1. **Keep controllers thin** - Put business logic in models/services
2. **Use framework helpers** - `view()`, `config()`, `auth()`, `validate_request()`
3. **Follow PSR-12** - Run `composer lint` before committing
4. **Type everything** - Use strict_types, typed properties/returns
5. **Module isolation** - Keep modules self-contained
6. **No raw SQL** - Use ORM/Query Builder
7. **Test behavior** - Not implementation details
8. **Validate input** - Sanitize all user data