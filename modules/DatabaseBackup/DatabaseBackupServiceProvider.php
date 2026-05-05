<?php

declare(strict_types=1);

namespace App\Modules\DatabaseBackup;

use App\Modules\DatabaseBackup\Support\BackupSettingsRepository;
use App\Modules\DatabaseBackup\Support\DatabaseBackupService;
use League\Container\Container;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Framework\Scheduling\Task;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Views\View;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class DatabaseBackupServiceProvider implements ModuleServiceProviderInterface
{
    private Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function register($app): void
    {
        $this->container->addShared(BackupSettingsRepository::class, new BackupSettingsRepository());
        $this->container->addShared(DatabaseBackupService::class, function () use ($app) {
            return new DatabaseBackupService(
                $app,
                $this->container->get(BackupSettingsRepository::class)
            );
        });

        if ($app->has(MenuRegistry::class)) {
            $app->make(MenuRegistry::class)->add([
                'name' => 'database-backup',
                'label' => 'Database Backups',
                'url' => '/admin/database-backups',
                'parent' => 'admin.system',
                'order' => 15,
                'icon' => 'database-zap',
            ]);
        }
    }

    public function boot($app): void
    {
        $app->registerTask(
            (new Task(
                'database-backup:scheduled',
                static function (): string {
                    return app(DatabaseBackupService::class)->runScheduledBackup()['message'];
                }
            ))
                ->description('Creates a database backup when the configured schedule is due.')
                ->everyMinute()
                ->withoutOverlapping()
                ->when(static function (): bool {
                    return app(DatabaseBackupService::class)->isScheduleDue(new \DateTimeImmutable());
                })
        );

        if (Runtime::isWeb() && $app->has(View::class)) {
            $app->view()->addNamespace('database_backup', __DIR__ . '/resources/views');
        }
    }
}
