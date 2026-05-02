<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Auth\Support\PermissionMigrationHelper;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use PHPUnit\Framework\TestCase;

final class PermissionMigrationHelperTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-permission-helper-' . bin2hex(random_bytes(6));

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

    public function testPermissionSeederSkipsWhenAuthTablesAreMissing(): void
    {
        $app = new Application($this->basePath);
        $GLOBALS['marwa_app'] = $app;
        $app->make(AppBootstrapper::class)->bootstrap();

        PermissionMigrationHelper::insertPermissions([
            ['name' => 'View Reports', 'slug' => 'reports.view', 'group' => 'reports'],
        ]);

        PermissionMigrationHelper::removePermissions([
            'reports.view',
        ]);

        $tables = $app->make(\Marwa\DB\Connection\ConnectionManager::class)
            ->getPdo()
            ->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
            ->fetchAll(\PDO::FETCH_COLUMN);

        self::assertSame([], $tables);
    }
}
