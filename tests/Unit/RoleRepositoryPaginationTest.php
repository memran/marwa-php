<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Auth\Support\RoleRepository;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use PHPUnit\Framework\TestCase;

final class RoleRepositoryPaginationTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-role-pagination-' . bin2hex(random_bytes(6));
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

        file_put_contents($this->basePath . '/database/database.sqlite', '');
    }

    protected function tearDown(): void
    {
        foreach ([
            $this->basePath . '/config/database.php',
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
        );

        parent::tearDown();
    }

    public function testPaginatedRolesSupportsSearchFilterAndSort(): void
    {
        $app = new Application($this->basePath);
        $GLOBALS['marwa_app'] = $app;
        $app->make(AppBootstrapper::class)->bootstrap();

        /** @var ConnectionManager $connections */
        $connections = $app->make(ConnectionManager::class);
        $pdo = $connections->getPdo();

        $pdo->exec(<<<'SQL'
CREATE TABLE roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    level INTEGER NOT NULL DEFAULT 1,
    description TEXT NULL,
    is_system INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NULL,
    updated_at TEXT NULL
)
SQL);

        $pdo->exec("INSERT INTO roles (name, slug, level, is_system) VALUES ('Admin', 'admin', 10, 1)");
        $pdo->exec("INSERT INTO roles (name, slug, level, is_system) VALUES ('Manager', 'manager', 5, 0)");
        $pdo->exec("INSERT INTO roles (name, slug, level, is_system) VALUES ('Editor', 'editor', 2, 0)");
        $pdo->exec("INSERT INTO roles (name, slug, level, is_system) VALUES ('Viewer', 'viewer', 1, 0)");

        $repo = new RoleRepository();

        $all = $repo->paginatedRoles('', 1, 25, 'level', 'desc', 'all');
        self::assertSame(4, $all['total']);
        self::assertCount(4, $all['data']);
        self::assertSame('admin', $all['data'][0]->getAttribute('slug'));

        $custom = $repo->paginatedRoles('', 1, 25, 'level', 'desc', 'custom');
        self::assertSame(3, $custom['total']);
        foreach ($custom['data'] as $role) {
            self::assertSame(0, (int) $role->getAttribute('is_system'));
        }

        $system = $repo->paginatedRoles('', 1, 25, 'level', 'desc', 'system');
        self::assertSame(1, $system['total']);
        self::assertSame('admin', $system['data'][0]->getAttribute('slug'));

        $search = $repo->paginatedRoles('edit', 1, 25, 'level', 'desc', 'all');
        self::assertSame(1, $search['total']);
        self::assertSame('editor', $search['data'][0]->getAttribute('slug'));

        $byNameAsc = $repo->paginatedRoles('', 1, 25, 'name', 'asc', 'all');
        self::assertSame('Admin', $byNameAsc['data'][0]->getAttribute('name'));
        self::assertSame('Viewer', $byNameAsc['data'][3]->getAttribute('name'));

        $paginated = $repo->paginatedRoles('', 1, 2, 'level', 'desc', 'all');
        self::assertSame(2, $paginated['per_page']);
        self::assertSame(4, $paginated['total']);
        self::assertSame(2, $paginated['last_page']);
        self::assertCount(2, $paginated['data']);

        $page2 = $repo->paginatedRoles('', 2, 2, 'level', 'desc', 'all');
        self::assertCount(2, $page2['data']);
    }

}
