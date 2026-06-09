<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Roles\Support\RoleDataTable;
use App\Modules\Users\Support\UserDataTable;
use App\Modules\Users\Support\UserRepository;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Authorization\Contracts\GateInterface;
use Marwa\Router\Http\RequestFactory;
use PHPUnit\Framework\TestCase;

final class ModuleDatatableBuilderTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-module-datatables-' . bin2hex(random_bytes(6));

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

    public function testUsersDatatableUsesNewBuilderAndPreservesQueryStrings(): void
    {
        $app = $this->bootstrapApp();
        $pdo = $this->connections($app)->getPdo();

        $this->createUsersSchema($pdo);
        $this->seedUserData($pdo);

        $table = (new UserDataTable(
            new UserRepository(),
            new AuthManager(),
        ))->make(RequestFactory::fromArrays(
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/admin/users',
                'HTTP_HOST' => 'example.test',
            ],
            [
                'q' => 'ada',
                'filters' => ['status' => 'active'],
                'sort' => 'name',
                'direction' => 'asc',
                'columns' => ['name', 'email', 'roleRelation.name', 'is_active', 'created_at'],
            ],
            []
        ))->paginate(10)->result();

        self::assertSame(1, $table->pagination()->total());
        self::assertCount(1, $table->rows());
        self::assertSame('Ada', $table->rows()[0]['cells']['name']['value']);
        self::assertSame('/admin/users/1', $table->rows()[0]['cells']['name']['href']);
        self::assertSame('Admin', $table->rows()[0]['cells']['roleRelation.name']['value']);
        self::assertSame('badge_stack', $table->rows()[0]['cells']['is_active']['type']);
        self::assertCount(2, $table->rows()[0]['cells']['is_active']['items']);
        self::assertSame('Protected', $table->rows()[0]['cells']['is_active']['items'][1]['value']);
        self::assertTrue($table->rows()[0]['bulk']['disabled']);
        self::assertStringContainsString('q=ada', $table->pagination()->pages()[0]->url);
        self::assertStringContainsString('filters%5Bstatus%5D=active', $table->pagination()->pages()[0]->url);
    }

    public function testUsersDatatableExposesRowActionsForPermittedUsers(): void
    {
        $app = $this->bootstrapApp();
        $pdo = $this->connections($app)->getPdo();

        $this->createUsersSchema($pdo);
        $this->seedUserData($pdo);

        $app->instance(GateInterface::class, new class implements GateInterface {
            public function authorize(string $ability, mixed $resource = null): bool
            {
                return $this->check($ability, $resource);
            }

            public function check(string $ability, mixed $resource = null): bool
            {
                return in_array($ability, ['users.view', 'users.edit', 'users.delete'], true);
            }

            public function denies(string $ability, mixed $resource = null): bool
            {
                return !$this->check($ability, $resource);
            }

            public function allows(string $ability, mixed $resource = null): bool
            {
                return $this->check($ability, $resource);
            }

            public function forUser(\Marwa\Framework\Authorization\Contracts\UserInterface $user): GateInterface
            {
                return $this;
            }

            public function before(callable $callback): GateInterface
            {
                return $this;
            }

            public function define(string $ability, callable $callback): GateInterface
            {
                return $this;
            }
        });

        $table = (new UserDataTable(
            new UserRepository(),
            new AuthManager(),
        ))->make(RequestFactory::fromArrays(
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/admin/users',
                'HTTP_HOST' => 'example.test',
            ],
            [
                'q' => 'grace',
                'columns' => ['name', 'email', 'roleRelation.name', 'is_active', 'created_at'],
            ],
            []
        ))->paginate(10)->result();

        self::assertCount(1, $table->rows());
        self::assertSame('view', $table->rows()[0]['actions'][0]['name']);
        self::assertSame('/admin/users/2', $table->rows()[0]['actions'][0]['href']);
        self::assertSame('edit', $table->rows()[0]['actions'][1]['name']);
        self::assertSame('delete', $table->rows()[0]['actions'][2]['name']);
    }

    public function testRolesDatatableUsesWithCountAndHidesProtectedDeleteAction(): void
    {
        $app = $this->bootstrapApp();
        $pdo = $this->connections($app)->getPdo();

        $this->createRolesSchema($pdo);
        $this->seedRoleData($pdo);

        $table = (new RoleDataTable())->make(RequestFactory::fromArrays(
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/admin/roles',
                'HTTP_HOST' => 'example.test',
            ],
            [
                'filters' => ['type' => 'custom'],
                'sort' => 'level',
                'direction' => 'desc',
                'columns' => ['name', 'slug', 'level', 'permissions_count', 'is_system', 'users_count'],
            ],
            []
        ))->paginate(10)->result();

        self::assertSame(1, $table->pagination()->total());
        self::assertCount(1, $table->rows());
        self::assertSame('Editor', $table->rows()[0]['cells']['name']['value']);
        self::assertSame('/admin/roles/2/edit', $table->rows()[0]['cells']['name']['href']);
        self::assertSame('1 permission', $table->rows()[0]['cells']['permissions_count']['value']);
        self::assertSame('1 user', $table->rows()[0]['cells']['users_count']['value']);
        self::assertTrue($table->rows()[0]['bulk']['disabled']);
        self::assertCount(1, $table->rows()[0]['actions']);
        self::assertSame('edit', $table->rows()[0]['actions'][0]['name']);
        self::assertStringContainsString('filters%5Btype%5D=custom', $table->pagination()->pages()[0]->url);
    }

    private function bootstrapApp(): Application
    {
        $app = new Application($this->basePath);
        $GLOBALS['marwa_app'] = $app;
        $app->make(AppBootstrapper::class)->bootstrap();

        return $app;
    }

    private function connections(Application $app): ConnectionManager
    {
        /** @var ConnectionManager $connections */
        $connections = $app->make(ConnectionManager::class);

        return $connections;
    }

    private function createUsersSchema(\PDO $pdo): void
    {
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
    }

    private function seedUserData(\PDO $pdo): void
    {
        $pdo->exec("INSERT INTO roles (name, slug, level, is_system) VALUES ('Admin', 'admin', 10, 1)");
        $pdo->exec("INSERT INTO roles (name, slug, level, is_system) VALUES ('User', 'user', 1, 0)");
        $pdo->exec("INSERT INTO users (name, email, password, role_id, is_active) VALUES ('Ada', 'ada@example.test', 'hash', 1, 1)");
        $pdo->exec("INSERT INTO users (name, email, password, role_id, is_active) VALUES ('Grace', 'grace@example.test', 'hash', 2, 0)");
    }

    private function createRolesSchema(\PDO $pdo): void
    {
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

        $pdo->exec(<<<'SQL'
CREATE TABLE permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    "group" TEXT NULL,
    description TEXT NULL,
    created_at TEXT NULL,
    updated_at TEXT NULL
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE role_permission (
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    PRIMARY KEY (role_id, permission_id)
)
SQL);
    }

    private function seedRoleData(\PDO $pdo): void
    {
        $pdo->exec("INSERT INTO roles (name, slug, level, is_system) VALUES ('Admin', 'admin', 10, 1)");
        $pdo->exec("INSERT INTO roles (name, slug, level, is_system) VALUES ('Editor', 'editor', 2, 0)");
        $pdo->exec("INSERT INTO permissions (name, slug) VALUES ('Manage Posts', 'posts.manage')");
        $pdo->exec("INSERT INTO role_permission (role_id, permission_id) VALUES (2, 1)");
        $pdo->exec("INSERT INTO users (name, email, password, role_id, is_active) VALUES ('Editor User', 'editor@example.test', 'hash', 2, 1)");
    }
}
