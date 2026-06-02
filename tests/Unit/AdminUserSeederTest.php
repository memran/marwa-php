<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Auth\Models\Role;
use App\Modules\Users\Models\User;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\Seeder\SeedRunner;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use PHPUnit\Framework\TestCase;

final class AdminUserSeederTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-admin-user-seeder-' . bin2hex(random_bytes(6));

        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/database', 0777, true);

        file_put_contents(
            $this->basePath . '/.env',
            "APP_ENV=testing\nAPP_KEY=0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\nTIMEZONE=UTC\nDB_ENABLED=1\nDB_CONNECTION=sqlite\nDB_DATABASE={$this->basePath}/database/database.sqlite\nADMIN_BOOTSTRAP_EMAIL=admin@marwa.test\nADMIN_BOOTSTRAP_PASSWORD=ExampleAdminPassword123!\n"
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
    }

    protected function tearDown(): void
    {
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
            $_ENV['ADMIN_BOOTSTRAP_EMAIL'],
            $_ENV['ADMIN_BOOTSTRAP_PASSWORD'],
            $_SERVER['APP_ENV'],
            $_SERVER['APP_KEY'],
            $_SERVER['TIMEZONE'],
            $_SERVER['DB_ENABLED'],
            $_SERVER['DB_CONNECTION'],
            $_SERVER['DB_DATABASE'],
            $_SERVER['ADMIN_BOOTSTRAP_EMAIL'],
            $_SERVER['ADMIN_BOOTSTRAP_PASSWORD']
        );

        parent::tearDown();
    }

    public function testModuleSeederCreatesTheDefaultAdminUser(): void
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
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role_id INTEGER NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    last_login_at TEXT NULL,
    deleted_at TEXT NULL,
    created_at TEXT NULL,
    updated_at TEXT NULL
)
SQL);

        $runner = new SeedRunner(
            $connections,
            null,
            'default',
            __DIR__ . '/../../modules/Users/database/seeders',
            'Database\\Seeders'
        );

        $runner->runAll();

        $user = User::findBy('email', 'admin@marwa.test');

        self::assertInstanceOf(User::class, $user);
        self::assertSame('Administrator', $user->getAttribute('name'));
        self::assertSame(1, (int) $user->getAttribute('is_active'));
        self::assertSame('admin', (string) Role::findById((int) $user->getAttribute('role_id'))?->getAttribute('slug'));
    }
}
