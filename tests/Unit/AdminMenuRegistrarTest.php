<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Auth\Contracts\AdminActorInterface;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Support\RolePolicy;
use App\Support\AdminMenuRegistrar;
use Marwa\Framework\Exceptions\MenuConfigurationException;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Module\Module;
use PHPUnit\Framework\TestCase;

final class AdminMenuRegistrarTest extends TestCase
{
    protected function tearDown(): void
    {
        RolePolicy::resetToDefaults();
        parent::tearDown();
    }

    public function testItRegistersEveryDeclaredModuleMenuItem(): void
    {
        $menu = new MenuRegistry();
        $user = $this->actor('admin', allowAllPermissions: true);
        $registrar = new AdminMenuRegistrar($menu, static fn (): AdminActorInterface => $user);

        $registrar->register($this->applicationModules());

        $names = $this->childNames($menu->tree());
        $expectedNames = $this->declaredMenuNames();
        $expectedNames[] = 'admin.search';
        sort($expectedNames);
        sort($names);

        self::assertSame($expectedNames, $names);
    }

    public function testItAppliesPermissionsAndDynamicRoleHierarchy(): void
    {
        RolePolicy::setRoleLevels([
            'user' => 1,
            'admin' => 10,
            'director' => 20,
        ]);

        $modules = [
            new Module('reports', '/modules/Reports', [
                'name' => 'Reports',
                'slug' => 'reports',
                'menu' => [
                    'name' => 'admin.reports',
                    'label' => 'Reports',
                    'url' => '/admin/reports',
                    'parent' => 'admin.administration',
                    'permission' => 'reports.view',
                    'roles' => ['admin'],
                ],
            ]),
        ];

        $menu = new MenuRegistry();
        $user = $this->actor('director', ['reports.view']);
        (new AdminMenuRegistrar($menu, static fn (): AdminActorInterface => $user))->register($modules);

        self::assertContains('admin.reports', $this->childNames($menu->tree()));

        $deniedMenu = new MenuRegistry();
        $deniedUser = $this->actor('director');
        (new AdminMenuRegistrar($deniedMenu, static fn (): AdminActorInterface => $deniedUser))->register($modules);

        self::assertNotContains('admin.reports', $this->childNames($deniedMenu->tree()));
    }

    public function testItRejectsInvalidManifestMenuItems(): void
    {
        $module = new Module('legacy', '/modules/Legacy', [
            'name' => 'Legacy',
            'slug' => 'legacy',
            'menu' => [
                'section' => 'Administration',
                'label' => 'Legacy',
                'route' => '/admin/legacy',
                'parent' => 'admin.administration',
            ],
        ]);

        $this->expectException(MenuConfigurationException::class);
        $this->expectExceptionMessage('Menu item [name] is required.');

        (new AdminMenuRegistrar(new MenuRegistry(), static fn (): ?AdminActorInterface => null))
            ->register([$module]);
    }

    /**
     * @dataProvider invalidAccessMetadataProvider
     * @param array<string, mixed> $access
     */
    public function testItRejectsInvalidAccessMetadata(array $access, string $message): void
    {
        $module = new Module('reports', '/modules/Reports', [
            'name' => 'Reports',
            'slug' => 'reports',
            'menu' => array_merge([
                'name' => 'admin.reports',
                'label' => 'Reports',
                'url' => '/admin/reports',
                'parent' => 'admin.administration',
            ], $access),
        ]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage($message);

        (new AdminMenuRegistrar(new MenuRegistry(), static fn (): ?AdminActorInterface => null))
            ->register([$module]);
    }

    /**
     * @return iterable<string, array{0:array<string, mixed>,1:string}>
     */
    public static function invalidAccessMetadataProvider(): iterable
    {
        yield 'permission is not a string' => [
            ['permission' => ['reports.view']],
            'menu permission must be a non-empty string',
        ];
        yield 'permission is empty' => [
            ['permission' => '  '],
            'menu permission must be a non-empty string',
        ];
        yield 'roles is not an array' => [
            ['roles' => 'admin'],
            'menu roles must be an array of non-empty strings',
        ];
        yield 'roles contains an invalid value' => [
            ['roles' => ['admin', '']],
            'menu roles must be an array of non-empty strings',
        ];
    }

    /**
     * @dataProvider invalidParentProvider
     * @param array<string, mixed> $parent
     */
    public function testItRejectsInvalidAdminMenuParents(array $parent): void
    {
        $module = new Module('reports', '/modules/Reports', [
            'name' => 'Reports',
            'slug' => 'reports',
            'menu' => array_merge([
                'name' => 'admin.reports',
                'label' => 'Reports',
                'url' => '/admin/reports',
            ], $parent),
        ]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'menu parent must be one of: admin.overview, admin.identity-access, admin.administration, admin.system-logs'
        );

        (new AdminMenuRegistrar(new MenuRegistry(), static fn (): ?AdminActorInterface => null))
            ->register([$module]);
    }

    /**
     * @return iterable<string, array{0:array<string, mixed>}>
     */
    public static function invalidParentProvider(): iterable
    {
        yield 'missing parent' => [[]];
        yield 'unknown parent' => [['parent' => 'admin.unknown']];
        yield 'non-string parent' => [['parent' => ['admin.administration']]];
    }

    /**
     * @return list<Module>
     */
    private function applicationModules(): array
    {
        $modules = [];

        foreach (glob(dirname(__DIR__, 2) . '/modules/*/manifest.php') ?: [] as $manifestPath) {
            $manifest = require $manifestPath;
            if (!is_array($manifest)) {
                continue;
            }

            $slug = $manifest['slug'] ?? null;
            if (!is_string($slug) || $slug === '') {
                continue;
            }

            $modules[] = new Module($slug, dirname($manifestPath), $manifest);
        }

        return $modules;
    }

    /**
     * @return list<string>
     */
    private function declaredMenuNames(): array
    {
        $names = [];

        foreach ($this->applicationModules() as $module) {
            $menu = $module->get('menu');
            if (!is_array($menu)) {
                continue;
            }

            $entries = array_is_list($menu) ? $menu : [$menu];
            foreach ($entries as $entry) {
                if (is_array($entry) && is_string($entry['name'] ?? null)) {
                    $names[] = $entry['name'];
                }
            }
        }

        return $names;
    }

    /**
     * @param list<string> $permissions
     */
    private function actor(
        string $roleSlug,
        array $permissions = [],
        bool $allowAllPermissions = false,
    ): AdminActorInterface {
        $role = new Role();
        $role->setAttribute('slug', $roleSlug);

        return new class ($role, $permissions, $allowAllPermissions) implements AdminActorInterface {
            /**
             * @param list<string> $permissions
             */
            public function __construct(
                private readonly Role $roleModel,
                private readonly array $permissions,
                private readonly bool $allowAllPermissions,
            ) {}

            public function getAttribute(string $key): mixed
            {
                return null;
            }

            public function getId(): int
            {
                return 1;
            }

            public function role(): Role
            {
                return $this->roleModel;
            }

            public function hasPermission(string $permission): bool
            {
                return $this->allowAllPermissions || in_array($permission, $this->permissions, true);
            }
        };
    }

    /**
     * @param list<array<string, mixed>> $tree
     * @return list<string>
     */
    private function childNames(array $tree): array
    {
        $names = [];

        foreach ($tree as $section) {
            foreach ((array) ($section['children'] ?? []) as $child) {
                if (is_array($child) && is_string($child['name'] ?? null)) {
                    $names[] = $child['name'];
                }
            }
        }

        return $names;
    }
}
