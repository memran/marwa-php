<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Contracts\AdminActorInterface;

final class AuthManager
{
    private readonly AdminSessionManager $sessionManager;
    private readonly PasswordResetMailer $passwordResetMailer;

    public function __construct(
        $sessionManager = null,
        $passwordResetMailer = null,
    ) {
        $this->sessionManager = $sessionManager instanceof AdminSessionManager
            ? $sessionManager
            : new AdminSessionManager();
        $this->passwordResetMailer = $passwordResetMailer instanceof PasswordResetMailer
            ? $passwordResetMailer
            : new PasswordResetMailer();
    }

    public function check(): bool
    {
        return $this->sessionManager->check();
    }

    public function user(): ?AdminActorInterface
    {
        return $this->sessionManager->user();
    }

    public function attempt(string $email, string $password): bool
    {
        return $this->sessionManager->attempt($email, $password);
    }

    public function lastFailureReason(): ?string
    {
        return $this->sessionManager->lastFailureReason();
    }

    public function logout(): void
    {
        $this->sessionManager->logout();
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
