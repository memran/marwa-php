<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Users\Models\User;
use App\Modules\Users\Support\UserActivityService;
use App\Modules\Users\Support\UserAdminGuard;
use App\Modules\Users\Support\UserListing;
use App\Modules\Users\Support\UserRepository;
use App\Modules\Users\Support\UserStatus;
use App\Support\AdminSearch;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use PHPUnit\Framework\TestCase;

final class UserRepositoryFilterTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-user-filter-' . bin2hex(random_bytes(6));
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

    public function testPaginatedUsersAppliesStatusFilters(): void
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
('Staff', 'staff', 1, 'Standard staff member', 0, datetime('now'), datetime('now'))
SQL);

        $pdo->exec(<<<'SQL'
INSERT INTO users (name, email, password, role_id, is_active, last_login_at, deleted_at, created_at, updated_at) VALUES
('Active Admin', 'active@example.test', 'hash', 1, 1, NULL, NULL, datetime('now'), datetime('now')),
('Disabled Staff', 'disabled@example.test', 'hash', 2, 0, NULL, NULL, datetime('now'), datetime('now')),
('Trashed Staff', 'trashed@example.test', 'hash', 2, 1, NULL, datetime('now'), datetime('now'), datetime('now'))
SQL);

        $listing = new UserListing(new AdminSearch());

        self::assertCount(3, $listing->paginatedUsers('', 1, 10, UserStatus::All)['data']);
        self::assertSame('active@example.test', (string) $listing->paginatedUsers('', 1, 10, UserStatus::Active)['data'][0]->getAttribute('email'));
        self::assertSame('disabled@example.test', (string) $listing->paginatedUsers('', 1, 10, UserStatus::Disabled)['data'][0]->getAttribute('email'));
        self::assertSame('trashed@example.test', (string) $listing->paginatedUsers('', 1, 10, UserStatus::Trashed)['data'][0]->getAttribute('email'));
    }
}
