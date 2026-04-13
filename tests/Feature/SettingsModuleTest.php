<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Settings\Support\SettingsCatalog;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\Schema\MigrationRepository;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\HttpKernel;
use Marwa\Framework\Supports\Runtime;
use Marwa\Router\Http\Input;
use Marwa\Router\Http\RequestFactory;
use PHPUnit\Framework\TestCase;

final class SettingsModuleTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        Runtime::setConsoleOverride(false);
        unset($GLOBALS['marwa_app']);
        Input::reset();
        $this->basePath = sys_get_temp_dir() . '/marwa-settings-' . bin2hex(random_bytes(6));

        $this->makeDirectory($this->basePath);
        $this->makeDirectory($this->basePath . '/config');
        $this->makeDirectory($this->basePath . '/routes');
        $this->makeDirectory($this->basePath . '/database');
        $this->makeDirectory($this->basePath . '/resources/views');
        $this->makeDirectory($this->basePath . '/resources/views/components');
        $this->makeDirectory($this->basePath . '/resources/views/themes/default/views/home');
        $this->makeDirectory($this->basePath . '/resources/views/themes/default/views/errors');
        $this->makeDirectory($this->basePath . '/resources/views/themes/admin');
        $this->makeDirectory($this->basePath . '/modules');
        $this->makeDirectory($this->basePath . '/bootstrap/cache');
        $this->makeDirectory($this->basePath . '/storage/cache');

        $this->copyDirectory(__DIR__ . '/../../config', $this->basePath . '/config');
        $this->copyDirectory(__DIR__ . '/../../database', $this->basePath . '/database');
        $this->copyDirectory(__DIR__ . '/../../resources/views/themes/default', $this->basePath . '/resources/views/themes/default');
        $this->copyDirectory(__DIR__ . '/../../resources/views/themes/admin', $this->basePath . '/resources/views/themes/admin');
        $this->copyDirectory(__DIR__ . '/../../modules', $this->basePath . '/modules');

        file_put_contents(
            $this->basePath . '/.env',
            "APP_ENV=testing\nAPP_NAME=\"Marwa Starter\"\nAPP_KEY=0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\nFRONTEND_THEME=default\nADMIN_THEME=admin\nTIMEZONE=UTC\nDB_ENABLED=1\nDB_CONNECTION=sqlite\nDB_DATABASE={$this->basePath}/database/database.sqlite\nAPP_CONFIG_CACHE={$this->basePath}/bootstrap/cache/config.php\nAPP_ROUTE_CACHE={$this->basePath}/bootstrap/cache/routes.php\nAPP_MODULE_CACHE={$this->basePath}/storage/cache/modules.php\nADMIN_BOOTSTRAP_EMAIL=admin@marwa.test\nADMIN_BOOTSTRAP_PASSWORD=ExampleAdminPassword123!\n"
        );

        putenv('APP_CONFIG_CACHE=' . $this->basePath . '/bootstrap/cache/config.php');
        putenv('APP_ROUTE_CACHE=' . $this->basePath . '/bootstrap/cache/routes.php');
        putenv('APP_MODULE_CACHE=' . $this->basePath . '/storage/cache/modules.php');
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

use App\Http\Controllers\HomeController;
use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\DashboardStatus\DashboardStatusCards;
use Marwa\Framework\Facades\Router;

Router::get('/', [HomeController::class, 'index'])->name('home')->register();

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class]], static function ($routes): void {
    $routes->get('/', static function (): \Psr\Http\Message\ResponseInterface {
        return view('dashboard/index', [
            'status_cards' => app(DashboardStatusCards::class)->cards(),
        ]);
    })->name('admin.dashboard')->register();
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
            $this->basePath . '/config/database.php',
            <<<PHP
<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => '{$this->basePath}/database/database.sqlite',
            'debug' => false,
        ],
    ],
    'debug' => false,
    'useDebugPanel' => false,
    'migrationsPath' => base_path('database/migrations'),
    'seedersPath' => base_path('database/seeders'),
    'seedersNamespace' => 'Database\\Seeders',
];
PHP
        );

        file_put_contents(
            $this->basePath . '/config/event.php',
            <<<'PHP'
<?php

declare(strict_types=1);

return [
    'listeners' => [],
    'subscribers' => [],
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

        file_put_contents($this->basePath . '/database/database.sqlite', '');
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
            'TIMEZONE',
            'DB_ENABLED',
            'DB_CONNECTION',
            'DB_DATABASE',
            'APP_CONFIG_CACHE',
            'APP_ROUTE_CACHE',
            'APP_MODULE_CACHE',
            'ADMIN_BOOTSTRAP_EMAIL',
            'ADMIN_BOOTSTRAP_PASSWORD',
        ] as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }

        @restore_error_handler();
        @restore_exception_handler();
        $this->removeDirectory($this->basePath);

        parent::tearDown();
    }

    public function testSettingsModuleLoadsDefaultsAndRefreshesRuntimeConfigAfterUpdate(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        (new AuthManager())->logout();
        $connections = $app->make(ConnectionManager::class);
        (new MigrationRepository($connections->getPdo(), $this->basePath . '/modules/Activity/database/migrations'))->migrate();
        (new MigrationRepository($connections->getPdo(), $this->basePath . '/modules/Settings/database/migrations'))->migrate();
        $kernel = $app->make(HttpKernel::class);

        self::assertSame('Marwa Starter', config('settings.app.name'));
        self::assertSame('default', config('settings.ui.theme'));
        self::assertGreaterThan(
            0,
            (int) $connections->getPdo()->query('SELECT COUNT(*) FROM settings')->fetchColumn()
        );

        $guest = $kernel->handle($this->request('GET', '/admin/settings'));
        self::assertSame(302, $guest->getStatusCode());
        self::assertSame('/admin/login', $guest->getHeaderLine('Location'));

        $loginPage = $kernel->handle($this->request('GET', '/admin/login'));
        self::assertSame(200, $loginPage->getStatusCode());
        $csrf = $app->security()->csrfToken();

        $login = $kernel->handle($this->request('POST', '/admin/login', [
            '_token' => $csrf,
            'email' => 'admin@marwa.test',
            'password' => 'ExampleAdminPassword123!',
        ]));
        self::assertSame(302, $login->getStatusCode());
        self::assertTrue((new AuthManager())->attempt('admin@marwa.test', 'ExampleAdminPassword123!'));

        $settingsPage = $kernel->handle($this->request('GET', '/admin/settings'));
        self::assertSame(200, $settingsPage->getStatusCode());
        self::assertStringContainsString('Save settings', (string) $settingsPage->getBody());
        self::assertStringContainsString('name="_token"', (string) $settingsPage->getBody());

        $payload = $this->settingsPayload([
            'app' => [
                'name' => 'Operations Console',
                'timezone' => 'Asia/Tokyo',
                'debug' => true,
            ],
            'email' => [
                'from_email' => 'alerts@example.test',
            ],
            'api' => [
                'allowed_origins' => ['https://a.example.test', 'https://b.example.test'],
            ],
            'logging' => [
                'level' => 'error',
            ],
        ]);
        $payload['_token'] = $csrf;

        $update = $kernel->handle($this->request('POST', '/admin/settings', $payload));
        self::assertSame(302, $update->getStatusCode());
        self::assertSame('/admin/settings', $update->getHeaderLine('Location'));

        self::assertSame('Operations Console', config('app.name'));
        self::assertSame('Operations Console', config('settings.app.name'));
        self::assertSame('alerts@example.test', config('mail.from.address'));
        self::assertSame(['https://a.example.test', 'https://b.example.test'], config('security.trustedOrigins'));
        self::assertSame('error', config('logger.storage.level'));
        self::assertSame('Asia/Tokyo', date_default_timezone_get());

        $cached = cache('settings.module.values');
        self::assertIsArray($cached);
        self::assertSame('Operations Console', $cached['app']['name']);

        $health = $kernel->handle($this->request('GET', '/health'));
        self::assertSame(200, $health->getStatusCode());
        self::assertStringContainsString('Operations Console', (string) $health->getBody());

        $updatedPage = $kernel->handle($this->request('GET', '/admin/settings'));
        self::assertSame(200, $updatedPage->getStatusCode());
        self::assertStringContainsString('Operations Console', (string) $updatedPage->getBody());
        self::assertStringContainsString('alerts@example.test', (string) $updatedPage->getBody());
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function settingsPayload(array $overrides): array
    {
        $catalog = new SettingsCatalog();
        $values = $this->mergeRecursive($catalog->defaults(), $overrides);
        $payload = ['settings' => []];

        foreach ($catalog->categories() as $category => $meta) {
            foreach ($meta['fields'] as $key => $field) {
                $value = $values[$category][$key];

                if (($field['input'] ?? null) === 'checkbox') {
                    if ($value) {
                        $payload['settings'][$category][$key] = '1';
                    }

                    continue;
                }

                if (($field['type'] ?? null) === 'list') {
                    $payload['settings'][$category][$key] = implode("\n", is_array($value) ? $value : []);
                    continue;
                }

                $payload['settings'][$category][$key] = (string) $value;
            }
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function mergeRecursive(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeRecursive($base[$key], $value);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    /**
     * @param array<string, mixed> $body
     */
    private function request(string $method, string $uri, array $body = []): \Psr\Http\Message\ServerRequestInterface
    {
        return RequestFactory::fromArrays(
            [
                'REQUEST_METHOD' => $method,
                'REQUEST_URI' => $uri,
                'HTTP_HOST' => 'example.test',
            ],
            [],
            $body
        );
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
