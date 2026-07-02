<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Contracts\AdminAuthenticatableInterface;
use App\Modules\Auth\Contracts\AdminUserProviderInterface;
use App\Modules\Auth\Mail\PasswordResetMail;
use App\Modules\Auth\Models\PasswordResetToken;

final class PasswordResetMailer
{
    private readonly AdminUserProviderInterface $users;

    public function __construct(?AdminUserProviderInterface $users = null)
    {
        $this->users = $users ?? new NullAdminUserProvider();
    }

    public function createPasswordResetLink(string $email, int $ttlMinutes = 30): ?string
    {
        $email = trim($email);

        if ($email === '') {
            return null;
        }

        $user = $this->users->findPersistedUserByEmail($email);
        if (!$user instanceof AdminAuthenticatableInterface) {
            return null;
        }

        $this->purgeExpiredPasswordResetTokens();
        $userId = $user->getId();
        if ($userId === null) {
            return null;
        }

        $this->purgePasswordResetTokensForUser($userId);

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + (max(1, $ttlMinutes) * 60));

        PasswordResetToken::create([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        $appUrl = config('app.url', 'http://localhost');
        if ($appUrl !== '' && str_ends_with((string) $appUrl, '/')) {
            $appUrl = substr((string) $appUrl, 0, -1);
        }

        return $appUrl . '/admin/reset-password/' . rawurlencode($token);
    }

    public function sendPasswordResetEmail(string $email, int $ttlMinutes = 30): bool
    {
        $email = trim($email);

        if ($email === '') {
            return false;
        }

        $user = $this->users->findPersistedUserByEmail($email);
        if (!$user instanceof AdminAuthenticatableInterface) {
            return false;
        }

        $resetLink = $this->createPasswordResetLink($email, $ttlMinutes);
        if ($resetLink === null) {
            return false;
        }

        $mail = new PasswordResetMail([
            'app_name' => (string) config('app.name', 'MarwaPHP'),
            'user_email' => (string) $user->getAttribute('email'),
            'user_name' => (string) $user->getAttribute('name'),
            'reset_link' => $resetLink,
            'expires_in_minutes' => $ttlMinutes,
        ]);

        try {
            $mail->queue();
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    public function resetPassword(string $token, string $password): bool
    {
        $token = trim($token);

        if ($token === '' || $password === '') {
            return false;
        }

        $record = $this->findPasswordResetToken($token);
        if (!$record instanceof PasswordResetToken) {
            return false;
        }

        $user = $this->users->findPersistedUserById((int) $record->getAttribute('user_id'));
        if (!$user instanceof AdminAuthenticatableInterface || !(bool) $user->getAttribute('is_active')) {
            return false;
        }

        $user->updatePasswordHash(password_hash($password, PASSWORD_DEFAULT));

        $record->delete();

        return true;
    }

    private function findPasswordResetToken(string $token): ?PasswordResetToken
    {
        $tokenHash = hash('sha256', $token);

        try {
            $record = PasswordResetToken::where('token_hash', '=', $tokenHash)
                ->where('expires_at', '>=', date('Y-m-d H:i:s'))
                ->first();

            return $record instanceof PasswordResetToken ? $record : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function purgePasswordResetTokensForUser(int $userId): void
    {
        PasswordResetToken::where('user_id', '=', $userId)
            ->delete();
    }

    private function purgeExpiredPasswordResetTokens(): void
    {
        PasswordResetToken::where('expires_at', '<', date('Y-m-d H:i:s'))
            ->delete();
    }
}
