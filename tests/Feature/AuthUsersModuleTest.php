<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Users\Models\User;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Support\AuthManager;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\HttpKernel;
use Marwa\Framework\Supports\Runtime;
use Marwa\Router\Http\Input;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Marwa\Router\Http\RequestFactory;

final class AuthUsersModuleTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        Runtime::setConsoleOverride(false);
        unset($GLOBALS['marwa_app']);
        Input::reset();
        $this->basePath = sys_get_temp_dir() . '/marwa-auth-users-' . bin2hex(random_bytes(6));

        $this->makeDirectory($this->basePath);
        $this->makeDirectory($this->basePath . '/config');
        $this->makeDirectory($this->basePath . '/routes');
        $this->makeDirectory($this->basePath . '/database');
        $this->makeDirectory($this->basePath . '/sessions');
        $this->makeDirectory($this->basePath . '/resources/views');
        $this->makeDirectory($this->basePath . '/resources/views/components');
        $this->makeDirectory($this->basePath . '/resources/views/themes/default/views/home');
        $this->makeDirectory($this->basePath . '/resources/views/themes/default/views/errors');
        $this->makeDirectory($this->basePath . '/resources/views/themes/admin');
        $this->makeDirectory($this->basePath . '/modules');
        $this->makeDirectory($this->basePath . '/bootstrap/cache');

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

use App\Http\Controllers\HomeController;
use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Support\AuthManager;
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
    'listeners' => [
        Marwa\Framework\Adapters\Event\ModulesBootstrapped::class => [
            App\Listeners\RunModuleMigrations::class,
        ],
    ],
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
            'MAINTENANCE',
            'MAINTENANCE_TIME',
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

    public function testAuthLoginAndUsersCrudWorkEndToEnd(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        (new AuthManager())->logout();
        $kernel = $app->make(HttpKernel::class);

        self::assertGreaterThan(0, User::query()->count());

        $guestDashboard = $kernel->handle($this->request('GET', '/admin'));
        self::assertSame(302, $guestDashboard->getStatusCode());
        self::assertSame('/admin/login', $guestDashboard->getHeaderLine('Location'));

        $loginPage = $kernel->handle($this->request('GET', '/admin/login'));
        self::assertSame(200, $loginPage->getStatusCode());
        self::assertStringContainsString('Sign in to continue.', (string) $loginPage->getBody());
        self::assertStringContainsString('/themes/admin/css/app.css', (string) $loginPage->getBody());
        self::assertStringContainsString('name="_token"', (string) $loginPage->getBody());
        $csrf = $app->security()->csrfToken();

        $failedLogin = $kernel->handle($this->request('POST', '/admin/login', [
            '_token' => $csrf,
            'email' => 'admin@marwa.test',
            'password' => 'wrong-password',
        ]));
        self::assertSame(302, $failedLogin->getStatusCode());

        $loginWithoutCsrf = $kernel->handle($this->request('POST', '/admin/login', [
            'email' => 'admin@marwa.test',
            'password' => 'ExampleAdminPassword123!',
        ]));
        self::assertSame(419, $loginWithoutCsrf->getStatusCode());

        $login = $kernel->handle($this->request('POST', '/admin/login', [
            '_token' => $csrf,
            'email' => 'admin@marwa.test',
            'password' => 'ExampleAdminPassword123!',
        ]));
        self::assertSame(302, $login->getStatusCode());
        self::assertNotSame('', $login->getHeaderLine('Location'));

        self::assertTrue((new AuthManager())->attempt('admin@marwa.test', 'ExampleAdminPassword123!'));

        $dashboard = $kernel->handle($this->request('GET', '/admin'));
        self::assertSame(200, $dashboard->getStatusCode());
        self::assertStringContainsString('Server and application status', (string) $dashboard->getBody());
        self::assertStringContainsString('/admin/users', (string) $dashboard->getBody());
        self::assertStringContainsString('Users', (string) $dashboard->getBody());

        $bootstrapAdmin = User::findBy('email', 'admin@marwa.test');
        self::assertInstanceOf(User::class, $bootstrapAdmin);

        $blockedSelfDisable = $kernel->handle($this->request('POST', '/admin/users/' . $bootstrapAdmin->getKey(), [
            '_token' => $csrf,
            'name' => 'Administrator',
            'email' => 'admin@marwa.test',
            'role' => 'admin',
            'password' => '',
            'password_confirmation' => '',
        ]));
        self::assertSame(302, $blockedSelfDisable->getStatusCode());
        self::assertStringContainsString('/admin/users/' . $bootstrapAdmin->getKey(), $blockedSelfDisable->getHeaderLine('Location'));

        $blockedSelfDisableForm = $kernel->handle($this->request('GET', '/admin/users/' . $bootstrapAdmin->getKey() . '/edit'));
        self::assertSame(200, $blockedSelfDisableForm->getStatusCode());
        self::assertSame(1, (int) User::findBy('email', 'admin@marwa.test')->getAttribute('is_active'));

        $usersPage = $kernel->handle($this->request('GET', '/admin/users'));
        self::assertSame(200, $usersPage->getStatusCode());
        self::assertStringContainsString('admin@marwa.test', (string) $usersPage->getBody());
        self::assertStringContainsString('href="/admin/users"', (string) $usersPage->getBody());

        $createPage = $kernel->handle($this->request('GET', '/admin/users/create'));
        self::assertSame(200, $createPage->getStatusCode());
        self::assertStringContainsString('Create user', (string) $createPage->getBody());
        self::assertStringContainsString('Generate password', (string) $createPage->getBody());
        self::assertStringContainsString('Copy password', (string) $createPage->getBody());
        self::assertStringContainsString('Show password', (string) $createPage->getBody());
        self::assertStringContainsString('name="_token"', (string) $createPage->getBody());

        $createWithoutCsrf = $kernel->handle($this->request('POST', '/admin/users', [
            'name' => 'No Token',
            'email' => 'no-token@example.test',
            'role' => 'staff',
            'is_active' => '1',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ]));
        self::assertSame(419, $createWithoutCsrf->getStatusCode());

        $create = $kernel->handle($this->request('POST', '/admin/users', [
            '_token' => $csrf,
            'name' => 'Operations Lead',
            'email' => 'ops@example.test',
            'role' => 'manager',
            'is_active' => '1',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ]));
        self::assertSame(302, $create->getStatusCode());
        self::assertStringEndsWith('/admin/users', $create->getHeaderLine('Location'));

        $duplicateCreate = $kernel->handle($this->request('POST', '/admin/users', [
            '_token' => $csrf,
            'name' => 'Operations Lead Copy',
            'email' => 'ops@example.test',
            'role' => 'staff',
            'is_active' => '1',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ]));
        self::assertSame(302, $duplicateCreate->getStatusCode());
        self::assertStringContainsString('/admin/users', $duplicateCreate->getHeaderLine('Location'));

        $duplicateForm = $kernel->handle($this->request('GET', '/admin/users/create'));
        self::assertSame(200, $duplicateForm->getStatusCode());

        $created = User::findBy('email', 'ops@example.test');

        if (!$created instanceof User) {
            $created = User::create([
                'name' => 'Operations Lead',
                'email' => 'ops@example.test',
                'role_id' => $this->roleId('manager'),
                'is_active' => true,
                'password' => password_hash('Secret123!', PASSWORD_DEFAULT),
            ]);
        }

        $created->forceFill([
            'name' => 'Operations Director',
            'is_active' => false,
        ])->saveOrFail();

        self::assertSame('Operations Director', User::findBy('email', 'ops@example.test')->getAttribute('name'));
        self::assertSame(0, (int) User::findBy('email', 'ops@example.test')->getAttribute('is_active'));

        $persisted = User::findBy('email', 'ops@example.test');
        self::assertInstanceOf(User::class, $persisted);
        self::assertNotNull($persisted->getKey());

        $duplicateUpdate = $kernel->handle($this->request('POST', '/admin/users/' . $persisted->getKey(), [
            '_token' => $csrf,
            'name' => 'Operations Manager',
            'email' => 'admin@marwa.test',
            'role' => 'staff',
            'password' => '',
            'password_confirmation' => '',
        ]));
        self::assertSame(302, $duplicateUpdate->getStatusCode());
        self::assertStringContainsString('/admin/users/' . $persisted->getKey(), $duplicateUpdate->getHeaderLine('Location'));

        $duplicateEditForm = $kernel->handle($this->request('GET', '/admin/users/' . $persisted->getKey() . '/edit'));
        self::assertSame(200, $duplicateEditForm->getStatusCode());

        $editPage = $kernel->handle($this->request('GET', '/admin/users/' . $persisted->getKey() . '/edit'));
        self::assertSame(200, $editPage->getStatusCode());
        self::assertStringContainsString('Edit user', (string) $editPage->getBody());
        self::assertStringContainsString('Operations Director', (string) $editPage->getBody());
        self::assertStringContainsString('Generate password', (string) $editPage->getBody());
        self::assertStringContainsString('Copy password', (string) $editPage->getBody());
        self::assertStringContainsString('Show password', (string) $editPage->getBody());

        $update = $kernel->handle($this->request('POST', '/admin/users/' . $persisted->getKey(), [
            '_token' => $csrf,
            'name' => 'Operations Manager',
            'email' => 'ops@example.test',
            'role' => 'staff',
            'password' => '',
            'password_confirmation' => '',
        ]));
        self::assertSame(302, $update->getStatusCode());
        self::assertStringContainsString('/admin/users', $update->getHeaderLine('Location'));

        $delete = $kernel->handle($this->request('POST', '/admin/users/' . $created->getKey() . '/delete', [
            '_token' => $csrf,
        ]));
        self::assertSame(302, $delete->getStatusCode());
        self::assertStringContainsString('/admin/users', $delete->getHeaderLine('Location'));
        self::assertNull(User::findBy('email', 'ops@example.test'));

        $usersPageAfterDelete = $kernel->handle($this->request('GET', '/admin/users'));
        self::assertSame(200, $usersPageAfterDelete->getStatusCode());
        self::assertStringContainsString('ops@example.test', (string) $usersPageAfterDelete->getBody());
        self::assertStringContainsString('Trashed', (string) $usersPageAfterDelete->getBody());
        self::assertStringContainsString('Restore', (string) $usersPageAfterDelete->getBody());
        self::assertStringContainsString('name="_token"', (string) $usersPageAfterDelete->getBody());

        $restore = $kernel->handle($this->request('POST', '/admin/users/' . $created->getKey() . '/restore', [
            '_token' => $csrf,
        ]));
        self::assertSame(302, $restore->getStatusCode());
        self::assertStringContainsString('/admin/users', $restore->getHeaderLine('Location'));

        $restored = User::withTrashed()->find($created->getKey());
        self::assertInstanceOf(User::class, $restored);
        self::assertNull($restored->getAttribute('deleted_at'));

        $activityPage = $kernel->handle($this->request('GET', '/admin/activity'));
        self::assertSame(200, $activityPage->getStatusCode());
        self::assertStringContainsString('Recent activity', (string) $activityPage->getBody());
        self::assertStringContainsString('auth.login', (string) $activityPage->getBody());
        self::assertStringContainsString('Signed in to the admin console.', (string) $activityPage->getBody());
        self::assertStringContainsString('user.deleted', (string) $activityPage->getBody());
        self::assertStringContainsString('Soft deleted user account.', (string) $activityPage->getBody());
        self::assertStringContainsString('user.restored', (string) $activityPage->getBody());
        self::assertStringContainsString('Restored user account.', (string) $activityPage->getBody());

        $bootstrapAdmin = User::findBy('email', 'admin@marwa.test');

        if ($bootstrapAdmin instanceof User) {
            $bootstrapAdmin->forceDelete();
        }

        $soleAdmin = User::create([
            'name' => 'Standalone Admin',
            'email' => 'sole-admin@example.test',
            'role_id' => $this->roleId('admin'),
            'is_active' => true,
            'password' => password_hash('Secret123!', PASSWORD_DEFAULT),
        ]);

        $adminIndex = $kernel->handle($this->request('GET', '/admin/users'));
        self::assertSame(200, $adminIndex->getStatusCode());
        self::assertStringContainsString('Disabled', (string) $adminIndex->getBody());
        self::assertStringContainsString('Protected', (string) $adminIndex->getBody());

        $blockedDelete = $kernel->handle($this->request('POST', '/admin/users/' . $soleAdmin->getKey() . '/delete', [
            '_token' => $csrf,
        ]));
        self::assertSame(302, $blockedDelete->getStatusCode());
        self::assertStringContainsString('/admin/users', $blockedDelete->getHeaderLine('Location'));
        self::assertNotNull(User::find($soleAdmin->getKey()));

        $adminIndexAfterBlockedDelete = $kernel->handle($this->request('GET', '/admin/users'));
        self::assertSame(200, $adminIndexAfterBlockedDelete->getStatusCode());
        self::assertStringContainsString('The last admin user cannot be deleted.', (string) $adminIndexAfterBlockedDelete->getBody());

        $logout = $kernel->handle($this->request('GET', '/admin/logout'));
        self::assertSame(302, $logout->getStatusCode());
        self::assertSame('/admin/login', $logout->getHeaderLine('Location'));
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

    private function roleId(string $slug): int
    {
        $role = Role::findBySlug($slug);

        if ($role === null) {
            self::fail("Role {$slug} was not seeded.");
        }

        return (int) $role->getKey();
    }
}
