<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Contracts\AdminAuthenticatableInterface;
use App\Modules\Auth\Contracts\AdminUserProviderInterface;

final class NullAdminUserProvider implements AdminUserProviderInterface
{
    public function findPersistedUserByEmail(string $email): ?AdminAuthenticatableInterface
    {
        return null;
    }

    public function findPersistedUserById(int $id): ?AdminAuthenticatableInterface
    {
        return null;
    }

    public function createBootstrapUser(string $name, string $email): AdminAuthenticatableInterface
    {
        return new BootstrapAdminUser($name, $email);
    }
}
