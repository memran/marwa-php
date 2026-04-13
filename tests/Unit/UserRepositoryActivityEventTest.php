<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Activity\Models\Activity;
use App\Modules\Users\Models\User;
use App\Modules\Users\Support\UserRepository;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use PHPUnit\Framework\TestCase;

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

    public function testUserRepositoryRecordsCrudActivityDirectly(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();

        /** @var ConnectionManager $manager */
        $manager = $app->make(ConnectionManager::class);
        $pdo = $manager->getPdo();

        $pdo->exec(<<<'SQL'
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    last_login_at TEXT NULL,
    deleted_at TEXT NULL,
    created_at TEXT NULL,
    updated_at TEXT NULL
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE activities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    action TEXT NOT NULL,
    description TEXT NOT NULL,
    actor_name TEXT NULL,
    actor_email TEXT NULL,
    subject_type TEXT NULL,
    subject_id INTEGER NULL,
    details TEXT NULL,
    created_at TEXT NULL,
    updated_at TEXT NULL
)
SQL);

        /** @var UserRepository $users */
        $users = $app->make(UserRepository::class);
        $actor = User::newInstance([
            'id' => 999,
            'name' => 'Administrator',
            'email' => 'admin@marwa.test',
            'role' => 'admin',
            'is_active' => true,
        ], false);

        $created = $users->createUser([
            'name' => 'Operations Lead',
            'email' => 'ops@example.test',
            'role' => 'manager',
            'is_active' => 1,
        ], 'Secret123!', $actor);

        $users->updateUser($created, [
            'name' => 'Operations Manager',
            'email' => 'ops@example.test',
            'role' => 'staff',
            'is_active' => 0,
        ], null, $actor);

        $users->deleteUser($created, $actor);
        $trashed = User::withTrashed()->find((int) $created->getKey());

        self::assertInstanceOf(User::class, $trashed);
        self::assertTrue($users->restoreUser($trashed, $actor));

        $rows = Activity::query()->getBaseBuilder()->orderBy('id', 'asc')->get();
        $actions = array_map(static fn (array|object $row): string => (string) (is_array($row) ? $row['action'] : $row->action), $rows);
        $descriptions = array_map(static fn (array|object $row): string => (string) (is_array($row) ? $row['description'] : $row->description), $rows);
        $details = array_map(static fn (array|object $row): string => (string) (is_array($row) ? $row['details'] : $row->details), $rows);

        self::assertSame(
            ['user.created', 'user.updated', 'user.deleted', 'user.restored'],
            $actions
        );
        self::assertContains('Created user ops@example.test.', $descriptions);
        self::assertContains('Updated user ops@example.test.', $descriptions);
        self::assertContains('Deleted user ops@example.test.', $descriptions);
        self::assertContains('Restored user ops@example.test.', $descriptions);
        self::assertStringContainsString('Created user account.', $details[0]);
        self::assertStringContainsString('Changed fields:', $details[1]);
        self::assertStringContainsString('Soft deleted user account.', $details[2]);
        self::assertStringContainsString('Restored user account.', $details[3]);
    }
}
