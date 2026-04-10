<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Models\PasswordResetToken;
use App\Modules\Users\Models\User;

final class AuthManager
{
    private const SESSION_USER_ID = 'admin_user_id';
    private const SESSION_USER_NAME = 'admin_user_name';
    private const SESSION_USER_EMAIL = 'admin_user_email';

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function user(): ?User
    {
        if (!$this->dbReady()) {
            return null;
        }

        $userId = session(self::SESSION_USER_ID);

        if (!is_numeric($userId)) {
            return null;
        }

        $user = User::find((int) $userId);

        if (!$user || !$user->getAttribute('is_active')) {
            $this->logout();

            return null;
        }

        return $user;
    }

    public function attempt(string $email, string $password): bool
    {
        if (!$this->dbReady()) {
            return false;
        }

        $user = User::findBy('email', $email);

        if (!$user || !$user->getAttribute('is_active')) {
            return false;
        }

        $hashed = (string) $user->getAttribute('password');

        if ($hashed === '' || !password_verify($password, $hashed)) {
            return false;
        }

        session()->regenerate(true);
        session()->set(self::SESSION_USER_ID, (int) $user->getKey());
        session()->set(self::SESSION_USER_NAME, (string) $user->getAttribute('name'));
        session()->set(self::SESSION_USER_EMAIL, (string) $user->getAttribute('email'));

        $user->forceFill([
            'last_login_at' => date('Y-m-d H:i:s'),
        ])->saveOrFail();

        return true;
    }

    public function logout(): void
    {
        if (!session()->isStarted()) {
            return;
        }

        session()->invalidate();
    }

    /**
     * @return array{token:string,url:string,expires_at:string}|null
     */
    public function createPasswordResetLink(string $email, int $ttlMinutes = 30): ?array
    {
        if (!$this->dbReady()) {
            return null;
        }

        $user = User::findBy('email', $email);

        if (!$user) {
            return null;
        }

        PasswordResetToken::newQuery()
            ->where('user_id', '=', (int) $user->getKey())
            ->delete();

        $token = bin2hex(random_bytes(24));
        $expiresAt = date('Y-m-d H:i:s', strtotime(sprintf('+%d minutes', max(1, $ttlMinutes))));

        PasswordResetToken::create([
            'user_id' => (int) $user->getKey(),
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
        ]);

        return [
            'token' => $token,
            'url' => '/admin/reset-password/' . rawurlencode($token),
            'expires_at' => $expiresAt,
        ];
    }

    public function resetPassword(string $token, string $password): bool
    {
        if (!$this->dbReady()) {
            return false;
        }

        $record = PasswordResetToken::newQuery()
            ->where('token_hash', '=', hash('sha256', $token))
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->first();

        if (!$record instanceof PasswordResetToken) {
            return false;
        }

        $user = User::find((int) $record->getAttribute('user_id'));

        if (!$user) {
            $record->delete();

            return false;
        }

        $user->forceFill([
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ])->saveOrFail();

        $record->delete();

        return true;
    }

    private function dbReady(): bool
    {
        return app()->has(\Marwa\DB\Connection\ConnectionManager::class);
    }
}
