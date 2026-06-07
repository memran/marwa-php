<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Activity\Models\Activity;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use PHPUnit\Framework\TestCase;

final class ActivityModelListingTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-activity-listing-' . bin2hex(random_bytes(6));
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

    public function testPaginatedActivitiesSupportsFilterAndSort(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();

        /** @var ConnectionManager $manager */
        $manager = $app->make(ConnectionManager::class);
        $pdo = $manager->getPdo();

        $pdo->exec(<<<'SQL'
CREATE TABLE activities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    action TEXT NOT NULL,
    description TEXT NOT NULL,
    actor_name TEXT NULL,
    actor_email TEXT NULL,
    ip_address TEXT NULL,
    user_agent TEXT NULL,
    subject_type TEXT NULL,
    subject_id INTEGER NULL,
    details TEXT NULL,
    created_at TEXT NULL,
    updated_at TEXT NULL
)
SQL);

        $pdo->exec(<<<'SQL'
INSERT INTO activities (action, description, actor_name, actor_email, ip_address, user_agent, subject_type, subject_id, details, created_at, updated_at) VALUES
('auth.login', 'Signed in', 'Admin', 'admin@example.test', '127.0.0.1', 'Mozilla', 'user', 1, NULL, datetime('now', '-2 minutes'), datetime('now', '-2 minutes')),
('user.deleted', 'Deleted user', 'Admin', 'admin@example.test', '127.0.0.1', 'Mozilla', 'user', 2, NULL, datetime('now', '-1 minutes'), datetime('now', '-1 minutes')),
('notification.created', 'Created notification', 'System', NULL, '127.0.0.1', 'Mozilla', 'notification', 3, NULL, datetime('now'), datetime('now'))
SQL);

        $activity = new Activity();
        $usersQuery = Activity::query();
        $usersBuilder = $usersQuery->getBaseBuilder();
        $activity->scopeSearch($usersBuilder, '');
        $activity->scopeFilter($usersBuilder, 'users');
        $activity->scopeSort($usersBuilder, 'action', 'asc');
        $users = $usersQuery->paginate(10, 1);
        self::assertSame(['user.deleted'], array_map(
            static fn ($activity): string => (string) $activity->getAttribute('action'),
            $users['data']
        ));

        $authQuery = Activity::query();
        $authBuilder = $authQuery->getBaseBuilder();
        $activity->scopeSearch($authBuilder, '');
        $activity->scopeFilter($authBuilder, 'auth');
        $activity->scopeSort($authBuilder, 'created_at', 'asc');
        $auth = $authQuery->paginate(10, 1);
        self::assertSame(['auth.login'], array_map(
            static fn ($activity): string => (string) $activity->getAttribute('action'),
            $auth['data']
        ));
    }
}
