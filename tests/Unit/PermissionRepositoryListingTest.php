<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Auth\Support\PermissionRepository;
use App\Support\AdminSearch;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use PHPUnit\Framework\TestCase;

final class PermissionRepositoryListingTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-permission-listing-' . bin2hex(random_bytes(6));
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

        file_put_contents($this->basePath . '/config/event.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    'listeners' => [],\n    'subscribers' => [],\n];\n");
        file_put_contents($this->basePath . '/database/database.sqlite', '');
    }

    protected function tearDown(): void
    {
        @restore_error_handler();
        @restore_exception_handler();

        foreach ([
            $this->basePath . '/config/database.php',
            $this->basePath . '/config/event.php',
            $this->basePath . '/database/database.sqlite',
            $this->basePath . '/.env',
        ] as $file) {
            @unlink($file);
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

        parent::tearDown();
    }

    public function testPaginatedGroupedFilteredSupportsSearchGroupSortAndPagination(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();

        /** @var ConnectionManager $manager */
        $manager = $app->make(ConnectionManager::class);
        $pdo = $manager->getPdo();

        $pdo->exec(<<<'SQL'
CREATE TABLE permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    "group" TEXT NOT NULL,
    description TEXT NULL,
    created_at TEXT NULL,
    updated_at TEXT NULL
)
SQL);

        $pdo->exec(<<<'SQL'
INSERT INTO permissions (name, slug, "group", description, created_at, updated_at) VALUES
('Alpha View', 'alpha.view', 'alpha', 'Alpha permission', datetime('now', '-3 minutes'), datetime('now', '-3 minutes')),
('Beta View', 'beta.view', 'beta', 'Beta permission', datetime('now', '-2 minutes'), datetime('now', '-2 minutes')),
('Gamma View', 'gamma.view', 'beta', 'Gamma permission', datetime('now', '-1 minutes'), datetime('now', '-1 minutes'))
SQL);

        $repository = new PermissionRepository(new AdminSearch());

        $result = $repository->paginatedGroupedFiltered('', '', 1, 2, 'name', 'desc');

        self::assertSame(['Gamma View', 'Beta View'], array_map(
            static fn ($permission): string => (string) $permission->getAttribute('name'),
            $result['data']
        ));
        self::assertSame(2, $result['per_page']);
        self::assertSame(2, $result['last_page']);
        self::assertSame(['beta'], array_keys($result['groups']));

        $filtered = $repository->paginatedGroupedFiltered('', 'beta', 1, 10, 'slug', 'asc');
        self::assertSame(['beta.view', 'gamma.view'], array_map(
            static fn ($permission): string => (string) $permission->getAttribute('slug'),
            $filtered['data']
        ));
    }
}
