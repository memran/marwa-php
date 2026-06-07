<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Users\Models\User;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use PHPUnit\Framework\TestCase;

final class UserModelCollectTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-user-model-collect-' . bin2hex(random_bytes(6));

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

    public function testCollectAllowsDerivingUserStatsFromOneDataset(): void
    {
        $app = new Application($this->basePath);
        $GLOBALS['marwa_app'] = $app;
        $app->make(AppBootstrapper::class)->bootstrap();

        /** @var ConnectionManager $connections */
        $connections = $app->make(ConnectionManager::class);
        $pdo = $connections->getPdo();

        $pdo->exec(<<<'SQL'
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    password TEXT NOT NULL,
    role_id INTEGER NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    last_login_at TEXT NULL,
    created_at TEXT NULL,
    updated_at TEXT NULL,
    deleted_at TEXT NULL
)
SQL);

        $pdo->exec("INSERT INTO users (name, email, password, role_id, is_active) VALUES ('Ada', 'ada@example.test', 'hash', 1, 1)");
        $pdo->exec("INSERT INTO users (name, email, password, role_id, is_active) VALUES ('Grace', 'grace@example.test', 'hash', 1, 0)");
        $pdo->exec("INSERT INTO users (name, email, password, role_id, is_active, deleted_at) VALUES ('Linus', 'linus@example.test', 'hash', 1, 1, '2026-06-07 10:00:00')");

        $users = User::collect();

        self::assertCount(3, $users);
        self::assertSame(2, $users->filter(static fn (User $user): bool =>
            trim((string) $user->getAttribute('deleted_at')) === ''
        )->count());
        self::assertSame(1, $users->filter(static fn (User $user): bool =>
            trim((string) $user->getAttribute('deleted_at')) === '' && (int) $user->getAttribute('is_active') === 1
        )->count());
        self::assertSame(1, $users->filter(static fn (User $user): bool =>
            trim((string) $user->getAttribute('deleted_at')) === '' && (int) $user->getAttribute('is_active') === 0
        )->count());
        self::assertSame(1, $users->filter(static fn (User $user): bool =>
            trim((string) $user->getAttribute('deleted_at')) !== ''
        )->count());
    }
}
