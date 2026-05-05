<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\DatabaseBackup\Support\BackupSettingsRepository;
use App\Modules\DatabaseBackup\Support\DatabaseBackupService;
use Marwa\DB\Facades\DB;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use PHPUnit\Framework\TestCase;

final class DatabaseBackupServiceTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-database-backup-' . bin2hex(random_bytes(6));

        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/database', 0777, true);
        mkdir($this->basePath . '/storage/app', 0777, true);

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
        $this->removeDirectory($this->basePath);

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

    public function testCreateBackupAndRestoreSelectedTablesRoundTripsData(): void
    {
        $app = $this->bootApp();

        $pdo = app(\Marwa\DB\Connection\ConnectionManager::class)->getPdo();
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL)');
        $pdo->exec('CREATE TABLE audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, message TEXT NOT NULL)');

        DB::table('users')->insert([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
        ]);
        DB::table('audit_logs')->insert([
            'message' => 'backup me later',
        ]);

        $settings = $app->make(BackupSettingsRepository::class);
        $service = $app->make(DatabaseBackupService::class);
        $normalized = $service->normalizeSettingsSubmission([
            'enabled' => '1',
            'mode' => 'daily_at',
            'time' => '02:00',
            'day_of_week' => 1,
            'day_of_month' => 1,
            'interval_minutes' => 1440,
            'storage_disk' => 'local',
            'storage_path' => 'database-backups',
            'archive_format' => 'zip',
            'scope' => 'selected',
            'tables' => 'users',
        ], $settings->defaults());

        self::assertSame([], $normalized['errors']);
        $settings->save($normalized['values']);

        $backup = $service->createBackup();
        self::assertFileExists($app->basePath('storage/app/' . $backup['path']));

        $pdo->exec('DROP TABLE audit_logs');
        $pdo->exec('DELETE FROM users');
        $pdo->exec("INSERT INTO users (name, email) VALUES ('Changed Name', 'changed@example.test')");

        $restore = $service->restoreFromStoredBackup($backup['path']);

        self::assertSame(['users'], $restore['tables']);
        self::assertSame(1, DB::table('users')->count());
        self::assertSame('Ada Lovelace', (string) DB::table('users')->value('name'));
        self::assertSame('ada@example.test', (string) DB::table('users')->value('email'));
        self::assertFalse($this->tableExists($pdo, 'audit_logs'));
    }

    public function testScheduleDueMatchesConfiguredDailyTime(): void
    {
        $app = $this->bootApp();
        $settings = $app->make(BackupSettingsRepository::class);
        $service = $app->make(DatabaseBackupService::class);

        $settings->save(array_replace_recursive($settings->defaults(), [
            'enabled' => true,
            'mode' => 'daily_at',
            'time' => '03:15',
        ]));

        self::assertTrue($service->isScheduleDue(new \DateTimeImmutable('2026-05-05 03:15:00')));
        self::assertFalse($service->isScheduleDue(new \DateTimeImmutable('2026-05-05 03:14:00')));
    }

    private function bootApp(): Application
    {
        $app = new Application($this->basePath);
        $GLOBALS['marwa_app'] = $app;
        $app->make(AppBootstrapper::class)->bootstrap();

        return $app;
    }

    private function tableExists(\PDO $pdo, string $table): bool
    {
        $statement = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = '" . str_replace("'", "''", $table) . "'");

        return $statement instanceof \PDOStatement && (bool) $statement->fetchColumn();
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = glob($path . '/*');
        if (is_array($items)) {
            foreach ($items as $item) {
                if (is_dir($item)) {
                    $this->removeDirectory($item);
                    continue;
                }

                @unlink($item);
            }
        }

        @rmdir($path);
    }
}
