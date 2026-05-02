<?php

declare(strict_types=1);

namespace App\Modules\BackgroundJobs;

use App\Modules\BackgroundJobs\Support\BackgroundJobRepository;
use League\Container\Container;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Views\View;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class BackgroundJobsServiceProvider implements ModuleServiceProviderInterface
{
    private Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function register($app): void
    {
        $this->container->addShared(BackgroundJobRepository::class, function () use ($app) {
            return new BackgroundJobRepository($app);
        });

        if ($app->has(MenuRegistry::class)) {
            $app->make(MenuRegistry::class)->add([
                'name' => 'background-jobs',
                'label' => 'Background Jobs',
                'url' => '/admin/background-jobs',
                'parent' => 'admin.system',
                'order' => 20,
                'icon' => 'clock-3',
                'permission' => 'background_jobs.view',
            ]);
        }
    }

    public function boot($app): void
    {
        if (Runtime::isWeb() && $app->has(View::class)) {
            $app->view()->addNamespace('background_jobs', __DIR__ . '/resources/views');
        }
    }
}
