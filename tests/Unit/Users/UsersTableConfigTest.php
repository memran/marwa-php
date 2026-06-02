<?php

declare(strict_types=1);

namespace Tests\Unit\Users;

use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use App\Modules\Users\Support\UserAccessPolicy;
use App\Modules\Users\Support\UserStatus;
use App\Modules\Users\Support\UsersTableConfig;
use App\Support\DataTable\DataTableRowActions;
use App\Support\Export\Column;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use PHPUnit\Framework\TestCase;

final class UsersTableConfigTest extends TestCase
{
    private string $basePath;
    private Application $app;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-users-table-config-' . bin2hex(random_bytes(6));
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

        file_put_contents(
            $this->basePath . '/config/event.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    'listeners' => [],\n    'subscribers' => [],\n];\n"
        );
        file_put_contents($this->basePath . '/database/database.sqlite', '');

        $this->app = new Application($this->basePath);
        $this->app->make(AppBootstrapper::class)->bootstrap();

        $pdo = $this->app->make(ConnectionManager::class)->getPdo();
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

        $pdo->exec("INSERT INTO roles (name, slug, level, description, is_system, created_at, updated_at) VALUES ('Admin', 'admin', 5, 'System administrator', 1, datetime('now'), datetime('now'))");
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
            if (is_file($file)) {
                @unlink($file);
            }
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
    }

    public function testPageTitleAndDescriptionReflectUsersContext(): void
    {
        $config = $this->makeConfig(protectedAdminId: null);

        self::assertSame('Registered users', $config->pageTitle());
        self::assertStringContainsString('Search, filter', $config->pageDescription());
        self::assertSame('/admin/users', $config->basePath());
    }

    public function testColumnOptionsListExpectedColumns(): void
    {
        $config = $this->makeConfig(protectedAdminId: null);

        $options = $config->columnOptions();

        self::assertSame(['name', 'email', 'role', 'status', 'last_login'], array_keys($options));
    }

    public function testSortableKeysExcludeStatus(): void
    {
        $config = $this->makeConfig(protectedAdminId: null);

        self::assertNotContains('status', $config->sortableKeys());
        self::assertContains('name', $config->sortableKeys());
    }

    public function testBuildRowProducesCellsActionsAndBulkForActiveUser(): void
    {
        $user = $this->createUser('Alice', 'alice@example.test', false);
        $config = $this->makeConfig(protectedAdminId: 99);

        $row = $config->buildRow($user);

        self::assertSame((string) $user->getKey(), $row['bulk']['id']);
        self::assertFalse($row['bulk']['disabled']);
        self::assertSame('Alice', $row['cells']['name']['value']);
        self::assertSame('/admin/users/' . $user->getKey(), $row['cells']['name']['href']);
        self::assertSame('alice@example.test', $row['cells']['email']['value']);
        self::assertSame('Admin', $row['cells']['role']['value']);
        self::assertCount(3, $row['actions']);
        self::assertSame('link', $row['actions'][0]['type']);
        self::assertSame('Profile', $row['actions'][0]['label']);
    }

    public function testBuildRowMarksProtectedUserBulkAsDisabled(): void
    {
        $user = $this->createUser('Last Admin', 'admin@example.test', false);
        $config = $this->makeConfig(protectedAdminId: (int) $user->getKey());

        $row = $config->buildRow($user);

        self::assertTrue($row['bulk']['disabled']);
        self::assertStringContainsString('last admin', strtolower($row['bulk']['title']));
        $statusValues = array_column($row['cells']['status']['items'], 'value');
        self::assertContains('Protected', $statusValues);
    }

    public function testBuildRowForTrashedUserIncludesRestoreAction(): void
    {
        $user = $this->createUser('Removed User', 'removed@example.test', true);
        $config = $this->makeConfig(protectedAdminId: 99);

        $row = $config->buildRow($user);

        self::assertTrue($row['bulk']['disabled']);
        $actionLabels = array_column($row['actions'], 'label');
        self::assertContains('Restore', $actionLabels);
        $restoreAction = array_values(array_filter(
            $row['actions'],
            static fn (array $a): bool => ($a['label'] ?? '') === 'Restore'
        ))[0];
        self::assertStringContainsString('/restore', $restoreAction['action']);
    }

    public function testFilterItemsIncludesAllUserStatusFilters(): void
    {
        $config = $this->makeConfig(protectedAdminId: null);
        $state = [
            'query' => '',
            'filter' => UserStatus::Active->value,
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 1,
        ];
        $buildUrl = static fn (array $s, array $cols = [], ?string $path = null): string => '?status=' . $s['filter'];

        $items = $config->filterItems($state, ['name'], $buildUrl);

        self::assertCount(count(UserStatus::cases()), $items);
        $activeItems = array_values(array_filter($items, static fn (array $i): bool => $i['active']));
        self::assertCount(1, $activeItems);
        self::assertSame(UserStatus::Active->label(), $activeItems[0]['label']);
    }

    public function testBuildExportColumnsReturnsAllExpectedColumns(): void
    {
        $config = $this->makeConfig(protectedAdminId: null);

        $columns = $config->buildExportColumns();

        self::assertContainsOnlyInstancesOf(Column::class, $columns);
        self::assertSame(['name', 'email', 'role', 'status', 'last_login'], array_map(
            static fn (Column $c): string => $c->key,
            $columns
        ));
    }

    public function testResolveExportColumnsFiltersToVisibleKeys(): void
    {
        $config = $this->makeConfig(protectedAdminId: null);

        $resolved = $config->resolveExportColumns(['name', 'email']);

        self::assertSame(['name', 'email'], array_map(static fn (Column $c): string => $c->key, $resolved));
    }

    public function testResolveExportColumnsFallsBackToAllWhenUnknownProvided(): void
    {
        $config = $this->makeConfig(protectedAdminId: null);

        $resolved = $config->resolveExportColumns(['unknown']);

        self::assertSame(['name', 'email', 'role', 'status', 'last_login'], array_map(
            static fn (Column $c): string => $c->key,
            $resolved
        ));
    }

    public function testExportsListsCsvAndPdf(): void
    {
        $config = $this->makeConfig(protectedAdminId: null);

        $exports = $config->exports();

        self::assertCount(2, $exports);
        self::assertSame('CSV', $exports[0]['label']);
        self::assertSame('csv', $exports[0]['format']);
        self::assertSame('PDF', $exports[1]['label']);
        self::assertSame('pdf', $exports[1]['format']);
        self::assertStringContainsString('/export', $exports[0]['url']);
        self::assertStringContainsString('/export.pdf', $exports[1]['url']);
    }

    public function testHiddenFieldsIncludesVisibleColumnsAndFiltersEmptyValues(): void
    {
        $config = $this->makeConfig(protectedAdminId: null);

        $fields = $config->hiddenFields(
            ['q' => 'admin', 'status' => 'all', 'page' => null, 'empty' => '', 'sort' => 'name'],
            ['name', 'email']
        );

        $names = array_column($fields, 'name');
        self::assertContains('q', $names);
        self::assertContains('status', $names);
        self::assertContains('sort', $names);
        self::assertNotContains('page', $names);
        self::assertNotContains('empty', $names);
        $columnsFields = array_filter($fields, static fn (array $f): bool => $f['name'] === 'columns[]');
        self::assertCount(2, $columnsFields);
    }

    private function makeConfig(?int $protectedAdminId): UsersTableConfig
    {
        $access = $this->createMock(UserAccessPolicy::class);
        $access->method('protectedAdminId')->willReturn($protectedAdminId);
        $access->method('isActiveSessionUser')->willReturn(false);

        $auth = new AuthManager();
        $rowActions = new DataTableRowActions();

        return new UsersTableConfig($access, $auth, $rowActions);
    }

    private function createUser(string $name, string $email, bool $trashed): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'role_id' => 1,
            'is_active' => 1,
            'password' => password_hash('Secret123!', PASSWORD_DEFAULT),
        ]);
        if ($trashed) {
            $user->forceFill(['deleted_at' => '2026-01-01 00:00:00'])->saveOrFail();
        }

        return $user->refresh();
    }
}
