<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Activity\Support\ActivityRecorder;
use App\Modules\Users\Models\User;

final class AuthManager
{
    private const SESSION_AUTHENTICATED = 'admin_authenticated';
    private const SESSION_USER_NAME = 'admin_user_name';
    private const SESSION_USER_EMAIL = 'admin_user_email';
    private const DEFAULT_ADMIN_NAME = 'Administrator';
    private const DEFAULT_ADMIN_EMAIL = 'admin@marwa.test';
    private const DEFAULT_ADMIN_PASSWORD = 'ExampleAdminPassword123!';

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function user(): ?User
    {
        if (!session(self::SESSION_AUTHENTICATED, false)) {
            return null;
        }

        $email = trim((string) session(self::SESSION_USER_EMAIL, ''));

        if ($email === '' || !$this->matchesConfiguredEmail($email)) {
            return null;
        }

        return User::newInstance([
            'id' => 0,
            'name' => trim((string) session(self::SESSION_USER_NAME, self::DEFAULT_ADMIN_NAME)) ?: self::DEFAULT_ADMIN_NAME,
            'email' => $email,
            'role' => 'admin',
            'is_active' => true,
        ], false);
    }

    public function attempt(string $email, string $password): bool
    {
        $email = trim($email);

        if (!$this->matchesConfiguredEmail($email) || !$this->matchesConfiguredPassword($password)) {
            return false;
        }

        session()->regenerate(true);
        session()->set(self::SESSION_AUTHENTICATED, true);
        session()->set(self::SESSION_USER_NAME, self::DEFAULT_ADMIN_NAME);
        session()->set(self::SESSION_USER_EMAIL, $this->configuredEmail());

        (new ActivityRecorder())->recordActorAction(
            'auth.login',
            'Signed in to the admin console.',
            $this->user(),
            'auth',
            null,
            [
                'summary' => 'Signed in to the admin console.',
                'state' => [
                    'Email' => $email,
                ],
            ]
        );

        return true;
    }

    public function logout(): void
    {
        $actor = $this->user();

        (new ActivityRecorder())->recordActorAction(
            'auth.logout',
            'Signed out of the admin console.',
            $actor,
            'auth'
        );

        $session = session();
        $session->start();
        $session->forget(self::SESSION_AUTHENTICATED);
        $session->forget(self::SESSION_USER_NAME);
        $session->forget(self::SESSION_USER_EMAIL);
        $session->invalidate();
    }

    public function createPasswordResetLink(string $email, int $ttlMinutes = 30): null
    {
        return null;
    }

    public function resetPassword(string $token, string $password): bool
    {
        return false;
    }

    private function configuredEmail(): string
    {
        $email = trim((string) env('ADMIN_BOOTSTRAP_EMAIL', self::DEFAULT_ADMIN_EMAIL));

        return $email !== '' ? $email : self::DEFAULT_ADMIN_EMAIL;
    }

    private function configuredPassword(): string
    {
        $password = (string) env('ADMIN_BOOTSTRAP_PASSWORD', self::DEFAULT_ADMIN_PASSWORD);

        return $password !== '' ? $password : self::DEFAULT_ADMIN_PASSWORD;
    }

    private function matchesConfiguredEmail(string $email): bool
    {
        return $email !== '' && strcasecmp($email, $this->configuredEmail()) === 0;
    }

    private function matchesConfiguredPassword(string $password): bool
    {
        return hash_equals($this->configuredPassword(), $password);
    }
}
