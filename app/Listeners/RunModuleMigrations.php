<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Modules\Users\Database\Seeders\AdminUserSeeder;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\Schema\MigrationRepository;
use Marwa\Framework\Adapters\Event\AbstractEvent;
use Marwa\Framework\Adapters\Event\AbstractEventListener;
use Marwa\Framework\Adapters\Event\ModulesBootstrapped;
use Marwa\Module\Contracts\ModuleRegistryInterface;

final class RunModuleMigrations extends AbstractEventListener
{
    public function handle(AbstractEvent $event): void
    {
        if (!$event instanceof ModulesBootstrapped) {
            return;
        }

        if (!app()->has(ConnectionManager::class) || !app()->has(ModuleRegistryInterface::class)) {
            return;
        }

        if (app()->has('module.migrations.bootstrapped')) {
            return;
        }

        /** @var ConnectionManager $manager */
        $manager = app(ConnectionManager::class);
        /** @var ModuleRegistryInterface $registry */
        $registry = app(ModuleRegistryInterface::class);

        $directories = [];

        foreach ($registry->all() as $module) {
            foreach ($module->migrations() as $migrationFile) {
                $directory = dirname($migrationFile);

                if (is_dir($directory)) {
                    $directories[$directory] = true;
                }
            }
        }

        $fallbackPaths = glob(base_path('modules/*/database/migrations'), GLOB_ONLYDIR) ?: [];

        foreach ($fallbackPaths as $migrationPath) {
            if (is_dir($migrationPath)) {
                $directories[$migrationPath] = true;
            }
        }

        foreach (array_keys($directories) as $migrationPath) {
            (new MigrationRepository($manager->getPdo(), $migrationPath))->migrate();
        }

        $this->seedStarterAdmin($registry);
        app()->set('module.migrations.bootstrapped', true);
    }

    private function seedStarterAdmin(ModuleRegistryInterface $registry): void
    {
        $seederFile = $this->resolveStarterAdminSeederFile($registry);

        if ($seederFile === null) {
            return;
        }

        require_once $seederFile;

        if (!class_exists(AdminUserSeeder::class)) {
            return;
        }

        /** @var AdminUserSeeder $seeder */
        $seeder = new AdminUserSeeder();
        $seeder->run();
    }

    private function resolveStarterAdminSeederFile(ModuleRegistryInterface $registry): ?string
    {
        $usersModule = $registry->get('users');
        if ($usersModule !== null) {
            $moduleSeederPath = $usersModule->path('database/seeders');
            if (is_string($moduleSeederPath)) {
                $moduleSeederFile = $moduleSeederPath . DIRECTORY_SEPARATOR . 'AdminUserSeeder.php';
                if (is_file($moduleSeederFile)) {
                    return $moduleSeederFile;
                }
            }
        }

        $legacySeederFile = base_path('database/seeders/AdminUserSeeder.php');

        return is_file($legacySeederFile) ? $legacySeederFile : null;
    }
}
