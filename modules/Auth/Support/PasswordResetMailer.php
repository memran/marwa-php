<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Mail\PasswordResetMail;
use App\Modules\Auth\Models\PasswordResetToken;
use App\Modules\Users\Models\User;
use Marwa\DB\Connection\ConnectionManager;

final class PasswordResetMailer
{
    public function __construct(private readonly AdminUserResolver $users)
    {
    }

    public function createPasswordResetLink(string $email, int $ttlMinutes = 30): ?string
    {
        $email = trim($email);

        if ($email === '') {
            return null;
        }

        $user = $this->users->findPersistedUserByEmail($email);
        if (!$user instanceof User) {
            return null;
        }

        $this->purgeExpiredPasswordResetTokens();
        $this->purgePasswordResetTokensForUser((int) $user->getKey());

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + (max(1, $ttlMinutes) * 60));

        PasswordResetToken::create([
            'user_id' => $user->getKey(),
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
        if (!$user instanceof User) {
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

        $user = User::find((int) $record->getAttribute('user_id'));
        if (!$user instanceof User || !(bool) $user->getAttribute('is_active')) {
            return false;
        }

        $user->fill([
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        $user->save();

        $record->delete();

        return true;
    }

    private function findPasswordResetToken(string $token): ?PasswordResetToken
    {
        if (!app()->has(ConnectionManager::class)) {
            return null;
        }

        $tokenHash = hash('sha256', $token);

        try {
            $row = PasswordResetToken::newQuery()->getBaseBuilder()
                ->where('token_hash', '=', $tokenHash)
                ->where('expires_at', '>=', date('Y-m-d H:i:s'))
                ->first();
        } catch (\Throwable) {
            return null;
        }

        return $row === null
            ? null
            : PasswordResetToken::newInstance(is_array($row) ? $row : (array) $row, true);
    }

    private function purgePasswordResetTokensForUser(int $userId): void
    {
        if (!app()->has(ConnectionManager::class)) {
            return;
        }

        PasswordResetToken::newQuery()->getBaseBuilder()
            ->where('user_id', '=', $userId)
            ->delete();
    }

    private function purgeExpiredPasswordResetTokens(): void
    {
        if (!app()->has(ConnectionManager::class)) {
            return;
        }

        PasswordResetToken::newQuery()->getBaseBuilder()
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->delete();
    }
}
