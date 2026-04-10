<?php

declare(strict_types=1);

namespace App\Listeners;

use Database\Seeders\AdminUserSeeder;
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

        if ($directories === []) {
            $fallbackPaths = glob(base_path('modules/*/database/migrations'), GLOB_ONLYDIR) ?: [];

            foreach ($fallbackPaths as $migrationPath) {
                if (is_dir($migrationPath)) {
                    $directories[$migrationPath] = true;
                }
            }
        }

        foreach (array_keys($directories) as $migrationPath) {
            (new MigrationRepository($manager->getPdo(), $migrationPath))->migrate();
        }

        $this->seedStarterAdmin();
        app()->set('module.migrations.bootstrapped', true);
    }

    private function seedStarterAdmin(): void
    {
        $seederFile = base_path('database/seeders/AdminUserSeeder.php');

        if (!is_file($seederFile)) {
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
}
