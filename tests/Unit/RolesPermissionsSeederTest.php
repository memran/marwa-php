<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Auth\database\Seeders\RolesPermissionsSeeder;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\Seeder\SeedRunner;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use PHPUnit\Framework\TestCase;

final class RolesPermissionsSeederTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-roles-permissions-' . bin2hex(random_bytes(6));

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
            $_SERVER['APP_ENV'],
            $_SERVER['APP_KEY'],
            $_SERVER['TIMEZONE'],
            $_SERVER['DB_ENABLED'],
            $_SERVER['DB_CONNECTION'],
            $_SERVER['DB_DATABASE']
        );

        parent::tearDown();
    }

    public function testUserRoleGetsDashboardPermissionOnly(): void
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
CREATE TABLE role_permission (
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    PRIMARY KEY (role_id, permission_id)
)
SQL);

        $pdo->exec("INSERT INTO roles (name, slug, level, is_system) VALUES ('User', 'user', 1, 0)");
        $pdo->exec("INSERT INTO permissions (name, slug, \"group\") VALUES ('View Dashboard', 'dashboard.view', 'dashboard')");
        $pdo->exec("INSERT INTO permissions (name, slug, \"group\") VALUES ('View Notifications', 'notifications.view', 'notifications')");

        $runner = new SeedRunner(
            $connections,
            null,
            'default',
            __DIR__ . '/../../modules/Auth/database/Seeders',
            'App\\Modules\\Auth\\database\\Seeders'
        );

        $runner->runAll();

        $role = Role::findBy('slug', 'user');
        self::assertInstanceOf(Role::class, $role);
        self::assertSame(['dashboard.view', 'notifications.view'], array_map(
            static fn (Permission $permission): string => (string) $permission->getAttribute('slug'),
            $role->permissions()
        ));
    }
}
