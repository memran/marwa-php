<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Users\Models\User;

final class AuthManager
{
    private ?AdminSessionManager $sessionManager;
    private ?PasswordResetService $passwordResetService;

    public function __construct(
        ?AdminSessionManager $sessionManager = null,
        ?PasswordResetService $passwordResetService = null
    ) {
        $this->sessionManager = $sessionManager;
        $this->passwordResetService = $passwordResetService;
    }

    public function check(): bool
    {
        return $this->sessionManager()->check();
    }

    public function user(): ?User
    {
        return $this->sessionManager()->user();
    }

    public function attempt(string $email, string $password): bool
    {
        return $this->sessionManager()->attempt($email, $password);
    }

    public function lastFailureReason(): ?string
    {
        return $this->sessionManager()->lastFailureReason();
    }

    public function logout(): void
    {
        $this->sessionManager()->logout();
    }

    public function createPasswordResetLink(string $email, int $ttlMinutes = 30): ?string
    {
        return $this->passwordResetService()->createPasswordResetLink($email, $ttlMinutes);
    }

    public function resetPassword(string $token, string $password): bool
    {
        return $this->passwordResetService()->resetPassword($token, $password);
    }

    private function sessionManager(): AdminSessionManager
    {
        return $this->sessionManager ??= app(AdminSessionManager::class);
    }

    private function passwordResetService(): PasswordResetService
    {
        return $this->passwordResetService ??= app(PasswordResetService::class);
    }
}
