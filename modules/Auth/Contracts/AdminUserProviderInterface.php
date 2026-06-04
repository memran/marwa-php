<?php

declare(strict_types=1);

namespace App\Modules\Auth\Contracts;

interface AdminUserProviderInterface
{
    public function findPersistedUserByEmail(string $email): ?AdminAuthenticatableInterface;

    public function findPersistedUserById(int $id): ?AdminAuthenticatableInterface;

    public function createBootstrapUser(string $name, string $email): AdminAuthenticatableInterface;
}
