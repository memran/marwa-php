<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Contracts\AdminActorInterface;

final class AuthManager
{
    private readonly AdminSessionManager $sessionManager;
    private readonly PasswordResetMailer $passwordResetMailer;

    public function __construct(
        ?AdminSessionManager $sessionManager = null,
        ?PasswordResetMailer $passwordResetMailer = null,
    ) {
        $this->sessionManager = $sessionManager ?? new AdminSessionManager();
        $this->passwordResetMailer = $passwordResetMailer ?? new PasswordResetMailer();
    }

    public function check(): bool
    {
        return $this->sessionManager->check();
    }

    public function user(): ?AdminActorInterface
    {
        return $this->sessionManager->user();
    }

    public function attempt(string $email, string $password, string $ipAddress = ''): bool
    {
        return $this->sessionManager->attempt($email, $password, $ipAddress);
    }

    public function lastFailureReason(): ?string
    {
        return $this->sessionManager->lastFailureReason();
    }

    public function logout(): void
    {
        $this->sessionManager->logout();
    }

    public function refreshSessionFor(\App\Modules\Auth\Contracts\AdminAuthenticatableInterface $user): void
    {
        $this->sessionManager->refreshSessionFor($user);
    }

    public function createPasswordResetLink(string $email, int $ttlMinutes = 30): ?string
    {
        return $this->passwordResetMailer->createPasswordResetLink($email, $ttlMinutes);
    }

    public function resetPassword(string $token, string $password): bool
    {
        return $this->passwordResetMailer->resetPassword($token, $password);
    }
}
