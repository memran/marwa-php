# MarwaPHP — AI Native PHP Framework

<p align="center">
  <img src="https://img.shields.io/packagist/v/memran/marwa-php?label=composer&style=flat" alt="Composer">
  <img src="https://img.shields.io/packagist/dependency-v/memran/marwa-php/php?label=PHP&style=flat" alt="PHP">
  <img src="https://img.shields.io/github/actions/workflow/status/memran/marwa-php/ci.yml?branch=main&label=PHPUnit&style=flat" alt="PHPUnit">
  <img src="https://img.shields.io/github/actions/workflow/status/memran/marwa-php/ci.yml?branch=main&label=PHPStan&style=flat" alt="PHPStan">
</p>

**Build intelligent applications with the first PHP framework designed for the AI era.**

MarwaPHP is an AI-native framework that brings smart automation to modern PHP development. From AI-powered code generation to intelligent task scheduling and built-in agent support — MarwaPHP is built to work seamlessly with AI tools and workflows.

> **Zero-boilerplate. Maximum intelligence. Pure PHP.**

---

## AI-Native Features

| Feature | Description |
|---------|-------------|
| **AI Code Generation** | Let AI generate controllers, models, and migrations with built-in scaffolding |
| **Smart Task Scheduling** | AI-assisted cron expressions and natural language scheduling |
| **Intelligent Queue** | Auto-scaling job processing with AI-driven worker management |
| **Built-in Agent Support** | First-class support for AI agents to interact with your application |
| **Activity Logging** | Track AI interactions and system events automatically |

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

- PHP 8.2+ (optimized for AI workloads)
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
docker compose --env-file docker.env -f docker-compose.nginx.yml up -d --build
```

Access at http://localhost:8080

For the Caddy stack:

```bash
cd docker
cp docker.caddy.env.example docker.caddy.env
docker compose --env-file docker.caddy.env -f docker-compose.caddy.yml up -d --build
```

Access at http://localhost

### Available Stacks

| Stack | Compose File | Port |
|-------|--------------|------|
| Nginx + PHP-FPM | `docker-compose.nginx.yml` | 8080 |
| Caddy + PHP-FPM | `docker-compose.caddy.yml` | 80 |

### Environment Variables

```env
DB_ENABLED=1
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_NAME=marwa
DB_USER=marwa
DB_PASSWORD=secret
DB_ROOT_PASSWORD=secret

ADMIN_BOOTSTRAP_EMAIL=admin@example.com
ADMIN_BOOTSTRAP_PASSWORD=SecurePassword123!

APP_ENV=production
APP_DEBUG=0
```

Use the `DB_*` variables as the single source of truth. The Docker Compose files map these values to the MariaDB container bootstrap variables.

### Common Operations

```bash
# View logs
docker compose --env-file docker.env -f docker-compose.nginx.yml logs -f

# Restart
docker compose --env-file docker.env -f docker-compose.nginx.yml restart

# Stop
docker compose --env-file docker.env -f docker-compose.nginx.yml down

# Reset database
docker compose --env-file docker.env -f docker-compose.nginx.yml down -v

# Rebuild
docker compose --env-file docker.env -f docker-compose.nginx.yml up -d --build
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
9. **Leverage AI** - Use Marwa's AI-native features for automation

---

## 15. AI Integration

MarwaPHP is built from the ground up to work with AI. Here's how to integrate:

### Connect Your AI Agent

```php
// Expose endpoints for AI agents to interact with
Route::post('/ai/agent/execute', [AgentController::class, 'execute'])
    ->middleware('ai.agent');
```

### Smart Task Scheduling with AI

```php
// AI understands natural language schedules
$app->registerTask(
    (new Task('ai:analyze', function () {
        return 'Analysis complete';
    }))
        ->description('Run AI analytics on user data')
        ->smart('every 2 hours during business days')
);
```

### AI-Powered Code Generation

```bash
# Generate module with AI assistance
php marwa ai:generate module Blog --intelligent
```

### Activity Logging for AI Actions

```php
use App\Modules\Activity\Support\ActivityRecorder;

// Track AI decisions and actions
ActivityRecorder::record([
    'user_id' => null, // AI agent
    'action' => 'ai_analyzed_data',
    'description' => 'AI agent processed 1,000 records',
    'module' => 'AI',
]);
```

---

## Built for the AI Era

MarwaPHP isn't just another PHP framework — it's a new paradigm. Built with AI-first thinking:

- **AI-native architecture** — Every component designed with AI integration in mind
- **Intelligent automation** — Let AI handle the boring stuff so you can focus on logic
- **Future-proof** — Built to evolve with the rapidly changing AI landscape
- **Developer experience** — AI-assisted debugging, testing, and deployment

> **Ship faster. Code smarter. Let AI do the heavy lifting.**

---

<p align="center">
  <a href="https://github.com/memran/marwa-php">GitHub</a> •
  <a href="https://packagist.org/packages/memran/marwa-php">Packagist</a> •
  <a href="https://marwa-php.dev">Documentation</a>
</p>
