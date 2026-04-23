<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Auth\Support\AuthManager;
use Laminas\Diactoros\ServerRequest;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Bootstrappers\ModuleBootstrapper;
use Marwa\Framework\HttpKernel;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Supports\Config;
use Marwa\Router\Http\Input;
use Marwa\Module\ModuleRepository;
use PHPUnit\Framework\TestCase;

final class StarterThemeRoutingTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        Runtime::setConsoleOverride(false);
        unset($GLOBALS['marwa_app']);
        Input::reset();
        $this->basePath = sys_get_temp_dir() . '/marwa-starter-' . bin2hex(random_bytes(6));

        $this->makeDirectory($this->basePath);
        $this->makeDirectory($this->basePath . '/config');
        $this->makeDirectory($this->basePath . '/routes');
        $this->makeDirectory($this->basePath . '/sessions');
        $this->makeDirectory($this->basePath . '/resources/views');
        $this->makeDirectory($this->basePath . '/resources/views/components');
        $this->makeDirectory($this->basePath . '/resources/views/themes/default/views/home');
        $this->makeDirectory($this->basePath . '/resources/views/themes/default/views/errors');
        $this->makeDirectory($this->basePath . '/resources/views/themes/admin');
        $this->makeDirectory($this->basePath . '/modules');
        $this->makeDirectory($this->basePath . '/bootstrap/cache');
        copy(__DIR__ . '/../../config/view.php', $this->basePath . '/config/view.php');
        copy(__DIR__ . '/../../config/security.php', $this->basePath . '/config/security.php');

        file_put_contents(
            $this->basePath . '/.env',
            "APP_ENV=testing\nAPP_NAME=\"Marwa Starter\"\nAPP_KEY=0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\nFRONTEND_THEME=default\nADMIN_THEME=admin\nTIMEZONE=UTC\nAPP_CONFIG_CACHE={$this->basePath}/bootstrap/cache/config.php\nAPP_ROUTE_CACHE={$this->basePath}/bootstrap/cache/routes.php\nAPP_MODULE_CACHE={$this->basePath}/storage/cache/modules.php\n"
        );

        putenv('APP_CONFIG_CACHE=' . $this->basePath . '/bootstrap/cache/config.php');
        putenv('APP_ROUTE_CACHE=' . $this->basePath . '/bootstrap/cache/routes.php');
        putenv('APP_MODULE_CACHE=' . $this->basePath . '/storage/cache/modules.php');
        ini_set('session.save_path', $this->basePath . '/sessions');
        $_ENV['APP_CONFIG_CACHE'] = $this->basePath . '/bootstrap/cache/config.php';
        $_ENV['APP_ROUTE_CACHE'] = $this->basePath . '/bootstrap/cache/routes.php';
        $_ENV['APP_MODULE_CACHE'] = $this->basePath . '/storage/cache/modules.php';
        $_SERVER['APP_CONFIG_CACHE'] = $this->basePath . '/bootstrap/cache/config.php';
        $_SERVER['APP_ROUTE_CACHE'] = $this->basePath . '/bootstrap/cache/routes.php';
        $_SERVER['APP_MODULE_CACHE'] = $this->basePath . '/storage/cache/modules.php';

        file_put_contents(
            $this->basePath . '/routes/web.php',
            <<<'PHP'
<?php

declare(strict_types=1);

use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use Marwa\Router\Response;
use Marwa\Framework\Facades\Router;

Router::get('/', [HomeController::class, 'index'])->name('home')->register();

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class]], static function ($routes): void {
    $routes->get('/', static fn (): \Psr\Http\Message\ResponseInterface => Response::html('Admin dashboard'))->name('admin.dashboard')->register();
});
PHP
        );

        file_put_contents(
            $this->basePath . '/routes/api.php',
            <<<'PHP'
<?php

declare(strict_types=1);

use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;

Router::get('/health', static fn (): \Psr\Http\Message\ResponseInterface => Response::json([
    'status' => 'ok',
    'app' => config('app.name', 'MarwaPHP'),
]))->name('health')->register();
PHP
        );

file_put_contents(
            $this->basePath . '/config/app.php',
            <<<'PHP'
<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'MarwaPHP'),
    'middlewares' => [
        Marwa\Framework\Middlewares\RequestIdMiddleware::class,
        Marwa\Framework\Middlewares\SessionMiddleware::class,
        App\Http\Middleware\NormalizeTrailingSlashMiddleware::class,
        App\Http\Middleware\ApplicationLifecycleMiddleware::class,
        Marwa\Framework\Middlewares\MaintenanceMiddleware::class,
        Marwa\Framework\Middlewares\SecurityMiddleware::class,
        Marwa\Framework\Middlewares\RouterMiddleware::class,
        Marwa\Framework\Middlewares\DebugbarMiddleware::class,
    ],
    'maintenance' => [
        'template' => 'maintenance.twig',
        'message' => 'Service temporarily unavailable for maintenance',
    ],
    'error404' => [
        'template' => 'errors/404.twig',
    ],
];
PHP
        );

        file_put_contents(
            $this->basePath . '/config/module.php',
            <<<'PHP'
<?php

declare(strict_types=1);

return [
    'enabled' => true,
];
PHP
        );

        $this->copyDirectory(__DIR__ . '/../../resources/views/themes/default', $this->basePath . '/resources/views/themes/default');
        $this->copyDirectory(__DIR__ . '/../../resources/views/themes/admin', $this->basePath . '/resources/views/themes/admin');
        $this->copyDirectory(__DIR__ . '/../../modules', $this->basePath . '/modules');

        file_put_contents(
            $this->basePath . '/resources/views/themes/default/views/home/index.twig',
            <<<'TWIG'
{% extends "layout.twig" %}

{% block content %}
<section>Frontend theme: {{ _theme_name }}</section>
{% endblock %}
TWIG
        );

        file_put_contents(
            $this->basePath . '/resources/views/themes/default/views/maintenance.twig',
            <<<'TWIG'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Maintenance</title>
</head>
<body>
    <h1>Maintenance mode</h1>
    <p>{{ message }}</p>
    <p>{{ estimated_recovery }}</p>
</body>
</html>
TWIG
        );

        file_put_contents(
            $this->basePath . '/resources/views/themes/default/views/errors/404.twig',
            <<<'TWIG'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>404 Not Found</title>
</head>
<body>
    <h1>404</h1>
    <p>The requested route {{ path }} was not found.</p>
    <p>{{ method }}</p>
</body>
</html>
TWIG
        );
    }

    protected function tearDown(): void
    {
        Runtime::setConsoleOverride(null);
        unset($GLOBALS['marwa_app']);

        foreach ([
            'APP_ENV',
            'APP_NAME',
            'APP_KEY',
            'FRONTEND_THEME',
            'ADMIN_THEME',
            'MAINTENANCE',
            'MAINTENANCE_TIME',
            'TIMEZONE',
            'APP_CONFIG_CACHE',
            'APP_ROUTE_CACHE',
            'APP_MODULE_CACHE',
        ] as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }

        @restore_error_handler();
        @restore_exception_handler();
        $this->removeDirectory($this->basePath);

        parent::tearDown();
    }

    public function testFrontendAndAdminRoutesUseTheirConfiguredThemes(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        (new AuthManager())->logout();
        $kernel = $app->make(HttpKernel::class);

        $frontend = $kernel->handle(new ServerRequest(uri: '/', method: 'GET'));
        $adminSlash = $kernel->handle(new ServerRequest(uri: '/admin/', method: 'GET'));
        $admin = $kernel->handle(new ServerRequest(uri: '/admin', method: 'GET'));
        $dashboard = $kernel->handle(new ServerRequest(uri: '/admin/dashboard', method: 'GET'));
        $logout = $kernel->handle(new ServerRequest(uri: '/admin/logout', method: 'GET'));
        $login = $kernel->handle(new ServerRequest(uri: '/admin/login', method: 'GET'));
        $forgot = $kernel->handle(new ServerRequest(uri: '/admin/forgot-password', method: 'GET'));
        $frontendAgain = $kernel->handle(new ServerRequest(uri: '/', method: 'GET'));
        $health = $kernel->handle(new ServerRequest(uri: '/health', method: 'GET'));

        self::assertSame(200, $frontend->getStatusCode());
        self::assertSame(302, $adminSlash->getStatusCode());
        self::assertSame('/admin/dashboard', $adminSlash->getHeaderLine('Location'));
        self::assertContains($admin->getStatusCode(), [200, 302]);
        if ($admin->getStatusCode() === 302) {
            self::assertSame('/admin/login', $admin->getHeaderLine('Location'));
        } else {
            self::assertStringContainsString('Admin dashboard', (string) $admin->getBody());
        }
        self::assertContains($dashboard->getStatusCode(), [200, 302]);
        if ($dashboard->getStatusCode() === 302) {
            self::assertSame('/admin/login', $dashboard->getHeaderLine('Location'));
        }
        self::assertSame(302, $logout->getStatusCode());
        self::assertSame('/admin/login', $logout->getHeaderLine('Location'));
        self::assertContains($login->getStatusCode(), [200, 302]);
        if ($login->getStatusCode() === 302) {
            self::assertSame('/admin', $login->getHeaderLine('Location'));
        } else {
            self::assertStringContainsString('Sign in to continue.', (string) $login->getBody());
            self::assertStringContainsString('Access the admin console with a lightweight session-backed login.', (string) $login->getBody());
            self::assertStringContainsString('/themes/admin/css/app.css', (string) $login->getBody());
        }

        self::assertSame(200, $forgot->getStatusCode());
        self::assertSame(200, $frontendAgain->getStatusCode());
        self::assertSame(200, $health->getStatusCode());
        self::assertStringContainsString('Frontend theme: default', (string) $frontend->getBody());
        self::assertStringContainsString('Request a recovery link.', (string) $forgot->getBody());
        self::assertStringContainsString('Generate a short-lived reset link for the matching admin account.', (string) $forgot->getBody());
        self::assertStringContainsString('Frontend theme: default', (string) $frontendAgain->getBody());
        self::assertStringContainsString('Marwa Starter', (string) $health->getBody());
    }

    public function testActivityModuleRouteLoadsAtTheAdminPath(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();

        $kernel = $app->make(HttpKernel::class);
        $activity = $kernel->handle(new ServerRequest(uri: '/admin/activity', method: 'GET'));

        self::assertContains($activity->getStatusCode(), [200, 302]);
        if ($activity->getStatusCode() === 302) {
            self::assertSame('/admin/login', $activity->getHeaderLine('Location'));
        } else {
            self::assertStringContainsString('Activity', (string) $activity->getBody());
        }
    }

    public function testMvpModulesAreDiscoveredAndRouted(): void
    {
        set_error_handler(static fn (): bool => false);
        set_exception_handler(static function (): void {});

        $repository = new ModuleRepository($this->basePath . '/modules');
        $modules = $repository->all(true);

        self::assertArrayHasKey('dashboard-status', $modules);

        foreach ([
            'auth',
            'users',
            'user-activity',
            'notifications',
            'settings',
        ] as $slug) {
            self::assertArrayHasKey($slug, $modules);
            $viewsPath = $modules[$slug]->path('views');
            if (is_string($viewsPath)) {
                self::assertDirectoryExists($viewsPath);
            }
        }

        foreach ([
            'auth' => 'App\\Modules\\Auth\\AuthServiceProvider',
            'users' => 'App\\Modules\\Users\\UsersServiceProvider',
            'user-activity' => 'App\\Modules\\Activity\\ActivityServiceProvider',
            'notifications' => 'App\\Modules\\Notifications\\NotificationsServiceProvider',
            'settings' => 'App\\Modules\\Settings\\SettingsServiceProvider',
        ] as $slug => $provider) {
            self::assertContains($provider, $modules[$slug]->providers());
            self::assertTrue(class_exists($provider));
            self::assertIsString($modules[$slug]->routeFile('http'));
            self::assertFileExists($modules[$slug]->routeFile('http'));
        }
    }

    public function testMaintenanceTemplateIsUsed(): void
    {
        file_put_contents(
            $this->basePath . '/.env',
            "APP_ENV=testing\nAPP_NAME=\"Marwa Starter\"\nAPP_KEY=0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\nFRONTEND_THEME=default\nADMIN_THEME=admin\nMAINTENANCE=1\nMAINTENANCE_TIME=120\nTIMEZONE=UTC\n"
        );

        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        $kernel = $app->make(HttpKernel::class);

        $maintenance = $kernel->handle(new ServerRequest(uri: '/', method: 'GET'));
        self::assertSame(503, $maintenance->getStatusCode());
        self::assertStringContainsString('Maintenance mode', (string) $maintenance->getBody());
        self::assertStringContainsString('Service temporarily unavailable for maintenance', (string) $maintenance->getBody());
    }

    public function testNotFoundTemplateIsUsed(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        $kernel = $app->make(HttpKernel::class);

        $notFound = $kernel->handle(new ServerRequest(uri: '/missing-page', method: 'GET'));

        self::assertSame(404, $notFound->getStatusCode());
        self::assertStringContainsString('The requested route /missing-page was not found.', (string) $notFound->getBody());
        self::assertStringContainsString('/missing-page', (string) $notFound->getBody());
        self::assertStringContainsString('GET', (string) $notFound->getBody());
    }

    private function makeDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        mkdir($path, 0777, true);
    }

    private function copyDirectory(string $source, string $destination): void
    {
        $this->makeDirectory($destination);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();

            if ($item->isDir()) {
                $this->makeDirectory($target);
                continue;
            }

            $this->makeDirectory(\dirname($target));
            copy($item->getPathname(), $target);
        }
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $itemPath = $item->getPathname();

            if ($item->isDir()) {
                @rmdir($itemPath);
                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }
}
