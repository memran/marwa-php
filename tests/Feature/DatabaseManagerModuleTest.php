<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Auth\Database\Seeders\RolesPermissionsSeeder;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\HttpKernel;
use Marwa\Framework\Supports\Runtime;
use Marwa\Router\Http\Input;
use Marwa\Router\Http\RequestFactory;
use PHPUnit\Framework\TestCase;

final class DatabaseManagerModuleTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        Runtime::setConsoleOverride(false);
        unset($GLOBALS['marwa_app']);
        Input::reset();
        $this->basePath = sys_get_temp_dir() . '/marwa-db-manager-' . bin2hex(random_bytes(6));

        $this->makeDirectory($this->basePath);
        $this->makeDirectory($this->basePath . '/config');
        $this->makeDirectory($this->basePath . '/routes');
        $this->makeDirectory($this->basePath . '/database');
        $this->makeDirectory($this->basePath . '/sessions');
        $this->makeDirectory($this->basePath . '/resources/views');
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

        ini_set('session.save_path', $this->basePath . '/sessions');

        file_put_contents(
            $this->basePath . '/routes/web.php',
            <<<'PHP'
<?php

declare(strict_types=1);

use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;

Router::get('/', static fn (): \Psr\Http\Message\ResponseInterface => Response::text('home'))->name('home')->register();
PHP
        );

        file_put_contents(
            $this->basePath . '/routes/api.php',
            <<<'PHP'
<?php

declare(strict_types=1);
PHP
        );

        file_put_contents($this->basePath . '/database/database.sqlite', '');
    }

    protected function tearDown(): void
    {
        Runtime::setConsoleOverride(null);
        unset($GLOBALS['marwa_app']);
        @restore_error_handler();
        @restore_exception_handler();
        $this->removeDirectory($this->basePath);

        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testDatabaseManagerIsAdminOnlyAndExecutesQueries(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        $kernel = $app->make(HttpKernel::class);
        $connections = $app->make(\Marwa\DB\Connection\ConnectionManager::class);
        (new \Marwa\DB\Schema\MigrationRepository($connections->getPdo(), $this->basePath . '/modules/Auth/database/migrations'))->migrate();
        if (!class_exists(RolesPermissionsSeeder::class, false)) {
            require_once $this->basePath . '/modules/Auth/Database/Seeders/RolesPermissionsSeeder.php';
        }
        (new RolesPermissionsSeeder())->run();

        db()->getPdo()->exec(<<<'SQL'
CREATE TABLE query_test_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
)
SQL);
        db()->getPdo()->exec("INSERT INTO query_test_users (name) VALUES ('Alpha'), ('Beta'), ('Gamma')");

        $guest = $kernel->handle($this->request('GET', '/admin/database'));
        self::assertSame(302, $guest->getStatusCode());
        self::assertSame('/admin/login', $guest->getHeaderLine('Location'));

        self::assertTrue((new AuthManager())->attempt('admin@marwa.test', 'ExampleAdminPassword123!'));
        $csrf = $app->security()->csrfToken();

        $page = $kernel->handle($this->request('GET', '/admin/database'));
        self::assertSame(200, $page->getStatusCode());
        self::assertStringContainsString('Database Manager', (string) $page->getBody());
        self::assertStringContainsString('high risk', strtolower((string) $page->getBody()));

        $select = $kernel->handle($this->request('POST', '/admin/database', [
            '_token' => $csrf,
            'query' => 'SELECT * FROM query_test_users ORDER BY id ASC',
        ]));
        self::assertSame(200, $select->getStatusCode());
        self::assertStringContainsString('Alpha', (string) $select->getBody());
        self::assertStringContainsString('Gamma', (string) $select->getBody());

        $blockedDelete = $kernel->handle($this->request('POST', '/admin/database', [
            '_token' => $csrf,
            'query' => 'DELETE FROM query_test_users WHERE id = 1',
        ]));
        self::assertSame(200, $blockedDelete->getStatusCode());
        self::assertStringContainsString('Tick the confirmation checkbox', (string) $blockedDelete->getBody());

        $confirmedDelete = $kernel->handle($this->request('POST', '/admin/database', [
            '_token' => $csrf,
            'query' => 'DELETE FROM query_test_users WHERE id = 1',
            'confirm_destructive' => '1',
        ]));
        self::assertSame(200, $confirmedDelete->getStatusCode());
        self::assertStringContainsString('Statement executed', (string) $confirmedDelete->getBody());

        self::assertSame(2, (int) db()->getPdo()->query('SELECT COUNT(*) FROM query_test_users')->fetchColumn());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testDatabaseManagerIsDisabledByDefaultInProduction(): void
    {
        file_put_contents(
            $this->basePath . '/.env',
            "APP_ENV=production\nAPP_NAME=\"Marwa Starter\"\nAPP_KEY=0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\nFRONTEND_THEME=default\nADMIN_THEME=admin\nTIMEZONE=UTC\nDB_ENABLED=1\nDB_CONNECTION=sqlite\nDB_DATABASE={$this->basePath}/database/database.sqlite\nAPP_CONFIG_CACHE={$this->basePath}/bootstrap/cache/config.php\nAPP_ROUTE_CACHE={$this->basePath}/bootstrap/cache/routes.php\nAPP_MODULE_CACHE={$this->basePath}/storage/cache/modules.php\nADMIN_BOOTSTRAP_EMAIL=admin@marwa.test\nADMIN_BOOTSTRAP_PASSWORD=ExampleAdminPassword123!\n"
        );

        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        $kernel = $app->make(HttpKernel::class);

        $guest = $kernel->handle($this->request('GET', '/admin/database'));
        self::assertSame(404, $guest->getStatusCode());
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
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
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
            } else {
                $this->makeDirectory(\dirname($target));
                copy($item->getPathname(), $target);
            }
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
            } else {
                @unlink($itemPath);
            }
        }

        @rmdir($path);
    }
}
