<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Activity\Events\ActivityRecordingRequested;
use App\Modules\Activity\Listeners\RecordActivityRecordingListener;
use App\Modules\Auth\Support\AuthManager;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use PHPUnit\Framework\TestCase;

final class ActivityRecordingRequestedListenerTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-activity-listener-' . bin2hex(random_bytes(6));

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

    public function testListenerPersistsRequestedActivity(): void
    {
        $app = new Application($this->basePath);
        $GLOBALS['marwa_app'] = $app;
        $app->make(AppBootstrapper::class)->bootstrap();

        /** @var ConnectionManager $connections */
        $connections = $app->make(ConnectionManager::class);
        $pdo = $connections->getPdo();

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

        $listener = new RecordActivityRecordingListener(new AuthManager());

        $listener->handle(new ActivityRecordingRequested(
            'user.created',
            'Created user.',
            'user',
            42,
            ['state' => ['name' => 'Ada']]
        ));

        $row = $pdo->query('SELECT * FROM activities LIMIT 1')->fetchObject();

        self::assertNotFalse($row);
        self::assertSame('user.created', $row->action);
        self::assertSame('Created user.', $row->description);
        self::assertSame('user', $row->subject_type);
        self::assertSame(42, (int) $row->subject_id);
        self::assertNotSame('', (string) $row->created_at);
    }
}
