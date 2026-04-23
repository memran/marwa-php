<?php

declare(strict_types=1);

namespace App\View\Extensions;

use App\Modules\Auth\Support\AuthManager;
use Marwa\Framework\Contracts\ViewExtensionInterface;
use Marwa\Framework\Views\Extension\AbstractViewExtension;

final class PermissionViewExtension extends AbstractViewExtension implements ViewExtensionInterface
{
    private bool $registered = false;

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->addFunction('can', function (string $permission): bool {
            return $this->checkPermission($permission);
        }, true);

        $this->addFunction('cannot', function (string $permission): bool {
            return !$this->checkPermission($permission);
        }, true);

        $this->addFunction('canany', function (array $permissions): bool {
            foreach ($permissions as $permission) {
                if ($this->checkPermission((string) $permission)) {
                    return true;
                }
            }
            return false;
        }, true);

        $this->addFunction('hasrole', function (string $role): bool {
            return $this->checkRole($role);
        }, true);

        $this->registered = true;
    }

    private function checkPermission(string $permission): bool
    {
        $user = app(AuthManager::class)->user();

        return $user?->hasPermission($permission) ?? false;
    }

    private function checkRole(string $role): bool
    {
        $user = app(AuthManager::class)->user();
        if ($user === null) {
            return false;
        }

        $userRole = $user->role();
        if ($userRole === null) {
            return false;
        }

        return $userRole->getAttribute('slug') === $role;
    }
}