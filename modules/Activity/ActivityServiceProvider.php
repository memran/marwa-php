<?php

declare(strict_types=1);

namespace App\Modules\Activity;

use Marwa\Framework\Views\View;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class ActivityServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
    }

    public function boot($app): void
    {
        if ($app->has(View::class)) {
            $app->view()->addNamespace('activity', __DIR__ . '/resources/views');
        }
    }
}
