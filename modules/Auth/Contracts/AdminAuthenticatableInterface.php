<?php

declare(strict_types=1);

namespace App\Modules\Auth\Contracts;

interface AdminAuthenticatableInterface extends AdminActorInterface
{
    public function getPasswordHash(): ?string;

    public function recordSuccessfulLogin(string $timestamp): void;

    public function updatePasswordHash(string $hash): void;
}
