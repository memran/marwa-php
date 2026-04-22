<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Users\Models\User;
use App\Modules\Users\Support\UserRepository;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Router\Http\RequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class UserRepositoryActivityEventTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-user-activity-' . bin2hex(random_bytes(6));
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

    public function testUserRepositoryCrudOperationsPersistWithoutDirectActivityWrites(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();

        /** @var ConnectionManager $manager */
        $manager = $app->make(ConnectionManager::class);
        $pdo = $manager->getPdo();

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

        $pdo->exec(<<<'SQL'
INSERT INTO roles (name, slug, level, description, is_system, created_at, updated_at) VALUES
('Admin', 'admin', 5, 'System administrator', 1, datetime('now'), datetime('now')),
('Manager', 'manager', 3, 'Operations manager', 0, datetime('now'), datetime('now')),
('Staff', 'staff', 1, 'Standard staff member', 0, datetime('now'), datetime('now'))
SQL);

        /** @var UserRepository $users */
        $users = $app->make(UserRepository::class);
        $app->add(ServerRequestInterface::class, RequestFactory::fromArrays(
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/admin/users',
                'HTTP_HOST' => 'example.test',
                'REMOTE_ADDR' => '203.0.113.10',
                'HTTP_USER_AGENT' => 'PHPUnit Browser/1.0',
            ],
            []
        ));
        $actor = User::newInstance([
            'id' => 999,
            'name' => 'Administrator',
            'email' => 'admin@marwa.test',
            'role_id' => 1,
            'is_active' => true,
        ], false);

        $created = $users->createUser([
            'name' => 'Operations Lead',
            'email' => 'ops@example.test',
            'role_id' => 2,
            'is_active' => 1,
        ], 'Secret123!', $actor);

        $users->updateUser($created, [
            'name' => 'Operations Manager',
            'email' => 'ops@example.test',
            'role_id' => 3,
            'is_active' => 0,
        ], null, $actor);

        $users->deleteUser($created, $actor);
        $trashed = User::withTrashed()->find((int) $created->getKey());

        self::assertInstanceOf(User::class, $trashed);
        self::assertTrue($users->restoreUser($trashed, $actor));
        $restored = User::findBy('email', 'ops@example.test');

        self::assertInstanceOf(User::class, $restored);
        self::assertNull($restored->getAttribute('deleted_at'));
        self::assertSame('Operations Manager', (string) $restored->getAttribute('name'));
        self::assertSame(0, (int) $restored->getAttribute('is_active'));
    }
}
