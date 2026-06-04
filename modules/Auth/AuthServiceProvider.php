<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Modules\Auth\Contracts\AdminUserProviderInterface;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Auth\Support\LoginAttemptTracker;
use App\Modules\Auth\Support\AdminSessionManager;
use App\Modules\Auth\Support\NullAdminUserProvider;
use App\Modules\Auth\Support\PasswordResetMailer;
use App\Modules\Auth\Support\RolePolicy;
use App\Support\ModuleDatabaseDependency;
use Marwa\Framework\Views\View;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class AuthServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
        $container = $app->container();

        $resolveUsers = static function () use ($container): AdminUserProviderInterface {
            if ($container->has(AdminUserProviderInterface::class)) {
                $provider = $container->get(AdminUserProviderInterface::class);

                if ($provider instanceof AdminUserProviderInterface) {
                    return $provider;
                }
            }

            return new NullAdminUserProvider();
        };

        $container->addShared(LoginAttemptTracker::class, fn () => new LoginAttemptTracker(), true);
        $container->addShared(AdminSessionManager::class, fn () => new AdminSessionManager(
            $resolveUsers(),
            $container->get(LoginAttemptTracker::class),
        ), true);
        $container->addShared(PasswordResetMailer::class, fn () => new PasswordResetMailer(
            $resolveUsers(),
        ), true);
        $container->addShared(AuthManager::class, fn () => new AuthManager(
            $container->get(AdminSessionManager::class),
            $container->get(PasswordResetMailer::class),
        ), true);
    }

    public function boot($app): void
    {
        if ($app->has(View::class)) {
            $app->make(View::class)->addNamespace('auth', __DIR__ . '/resources/views');
        }

        ModuleDatabaseDependency::boot(__DIR__, $app, static function (): void {
            RolePolicy::loadFromDatabase();
        });
    }
}
