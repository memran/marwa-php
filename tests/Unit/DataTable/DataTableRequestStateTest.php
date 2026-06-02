<?php

declare(strict_types=1);

namespace Tests\Unit\DataTable;

use App\Support\AdminListState;
use App\Support\DataTable\DataTableRequestState;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Router\Http\Input;
use Marwa\Router\Http\RequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class DataTableRequestStateTest extends TestCase
{
    private string $basePath;
    private Application $app;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-data-table-state-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/database', 0777, true);

        file_put_contents(
            $this->basePath . '/.env',
            "APP_ENV=testing\nAPP_KEY=0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\nTIMEZONE=UTC\nDB_ENABLED=1\nDB_CONNECTION=sqlite\nDB_DATABASE={$this->basePath}/database/database.sqlite\n"
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
];
PHP
        );

        file_put_contents(
            $this->basePath . '/config/event.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    'listeners' => [],\n    'subscribers' => [],\n];\n"
        );
        file_put_contents($this->basePath . '/database/database.sqlite', '');

        $this->app = new Application($this->basePath);
        $this->app->make(AppBootstrapper::class)->bootstrap();
    }

    protected function tearDown(): void
    {
        @restore_error_handler();
        @restore_exception_handler();

        Input::reset();

        foreach ([
            $this->basePath . '/config/database.php',
            $this->basePath . '/config/event.php',
            $this->basePath . '/database/database.sqlite',
            $this->basePath . '/.env',
        ] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath . '/database');
        @rmdir($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['APP_KEY'],
            $_ENV['TIMEZONE'],
            $_ENV['DB_ENABLED'],
            $_ENV['DB_CONNECTION'],
            $_ENV['DB_DATABASE'],
            $_SERVER['APP_ENV'],
            $_SERVER['APP_KEY'],
            $_SERVER['TIMEZONE'],
            $_SERVER['DB_ENABLED'],
            $_SERVER['DB_CONNECTION'],
            $_SERVER['DB_DATABASE']
        );
    }

    public function testResolveNormalizesParamsUsingListState(): void
    {
        $state = (new DataTableRequestState(new AdminListState()))->resolve([
            'q' => '  alice  ',
            'status' => ' active ',
            'sort' => ' name ',
            'direction' => 'ASC',
            'page' => '3',
        ]);

        self::assertSame('alice', $state['query']);
        self::assertSame('active', $state['filter']);
        self::assertSame('name', $state['sort']);
        self::assertSame('asc', $state['direction']);
        self::assertSame(3, $state['page']);
    }

    public function testResolveProvidesDefaultsForMissingKeys(): void
    {
        $state = (new DataTableRequestState(new AdminListState()))->resolve([]);

        self::assertSame('', $state['query']);
        self::assertSame('all', $state['filter']);
        self::assertSame('created_at', $state['sort']);
        self::assertSame('desc', $state['direction']);
        self::assertSame(1, $state['page']);
    }

    public function testBulkSelectedIdsReadsIdsFromParsedBody(): void
    {
        $request = RequestFactory::fromArrays(
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/users/bulk-delete', 'HTTP_HOST' => 'example.test'],
            [],
            ['ids' => ['1', '2', '3', '0', '-1', 'foo', '2']]
        );
        $this->app->add(ServerRequestInterface::class, $request);
        Input::setRequest($request);

        $ids = (new DataTableRequestState(new AdminListState()))->bulkSelectedIds();

        self::assertSame([1, 2, 3], $ids);
    }

    public function testBulkSelectedIdsReturnsEmptyWhenIdsAreMissing(): void
    {
        $request = RequestFactory::fromArrays(
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/users/bulk-delete', 'HTTP_HOST' => 'example.test'],
            [],
            []
        );
        $this->app->add(ServerRequestInterface::class, $request);
        Input::setRequest($request);

        self::assertSame([], (new DataTableRequestState(new AdminListState()))->bulkSelectedIds());
    }

    public function testBulkStatusLowercasesAndTrimsInput(): void
    {
        $request = RequestFactory::fromArrays(
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/users/bulk-status', 'HTTP_HOST' => 'example.test'],
            [],
            ['bulk_status' => '  ACTIVE  ']
        );
        $this->app->add(ServerRequestInterface::class, $request);
        Input::setRequest($request);

        self::assertSame('active', (new DataTableRequestState(new AdminListState()))->bulkStatus());
    }
}
