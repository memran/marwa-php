<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use Marwa\Framework\Views\View;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class AuthServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
    }

    public function boot($app): void
    {
        if ($app->has(View::class)) {
            $app->view()->addNamespace('auth', __DIR__ . '/resources/views');
        }
    }
}
