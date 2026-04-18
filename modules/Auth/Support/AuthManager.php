<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Activity\Support\ActivityRecorder;
use App\Modules\Users\Models\User;
use Marwa\Framework\Authorization\AuthManager as FrameworkAuthManager;

final class AuthManager
{
    private const SESSION_AUTHENTICATED = 'admin_authenticated';
    private const SESSION_USER_NAME = 'admin_user_name';
    private const SESSION_USER_EMAIL = 'admin_user_email';
    private const SESSION_LOGIN_FAILURES = 'admin_login_failures';
    private const DEFAULT_ADMIN_NAME = 'Administrator';
    private const DEFAULT_ADMIN_EMAIL = 'admin@marwa.test';
    private const DEFAULT_ADMIN_PASSWORD = 'ExampleAdminPassword123!';

    private ?string $lastFailureReason = null;

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function user(): ?User
    {
        if (!session(self::SESSION_AUTHENTICATED, false)) {
            $this->syncFrameworkLogout();

            return null;
        }

        $email = trim((string) session(self::SESSION_USER_EMAIL, ''));

        if ($email === '' || !$this->matchesConfiguredEmail($email)) {
            $this->syncFrameworkLogout();

            return null;
        }

        $persistedUser = $this->findPersistedUserByEmail($email);
        if ($persistedUser instanceof User) {
            $this->syncFrameworkUser($persistedUser);

            return $persistedUser;
        }

        $fallbackUser = User::newInstance([
            'id' => 0,
            'name' => trim((string) session(self::SESSION_USER_NAME, self::DEFAULT_ADMIN_NAME)) ?: self::DEFAULT_ADMIN_NAME,
            'email' => $email,
            'role_id' => $this->adminRoleId(),
            'role' => 'admin',
            'is_active' => true,
        ], false);

        $this->syncFrameworkUser($fallbackUser);

        return $fallbackUser;
    }

    public function attempt(string $email, string $password): bool
    {
        $email = trim($email);
        $this->lastFailureReason = null;

        if ($this->matchesConfiguredEmail($email) && $this->matchesConfiguredPassword($password)) {
            $this->clearLoginFailures($email);
            session()->regenerate(true);
            session()->set(self::SESSION_AUTHENTICATED, true);
            session()->set(self::SESSION_USER_NAME, self::DEFAULT_ADMIN_NAME);
            session()->set(self::SESSION_USER_EMAIL, $this->configuredEmail());

            $user = $this->user();
            (new ActivityRecorder())->recordActorAction(
                'auth.login',
                'Signed in to the admin console.',
                $user,
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

        if ($this->isLoginRateLimited($email)) {
            $this->lastFailureReason = 'rate_limited';

            return false;
        }

        $this->recordLoginFailure($email);
        $this->lastFailureReason = 'invalid_credentials';

        return false;
    }

    public function lastFailureReason(): ?string
    {
        return $this->lastFailureReason;
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

        $this->syncFrameworkLogout();

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

    private function loginAttemptLimit(): int
    {
        return max(1, (int) config('settings.lifecycle.security.login_attempt_limit', 5));
    }

    private function loginFailures(string $email): int
    {
        $failures = session(self::SESSION_LOGIN_FAILURES, []);

        if (!is_array($failures)) {
            return 0;
        }

        return max(0, (int) ($failures[$this->loginFailureKey($email)] ?? 0));
    }

    private function recordLoginFailure(string $email): void
    {
        $failures = session(self::SESSION_LOGIN_FAILURES, []);

        if (!is_array($failures)) {
            $failures = [];
        }

        $key = $this->loginFailureKey($email);
        $failures[$key] = $this->loginFailures($email) + 1;
        session()->set(self::SESSION_LOGIN_FAILURES, $failures);
    }

    private function clearLoginFailures(string $email): void
    {
        $failures = session(self::SESSION_LOGIN_FAILURES, []);

        if (!is_array($failures)) {
            return;
        }

        $key = $this->loginFailureKey($email);

        if (!array_key_exists($key, $failures)) {
            return;
        }

        unset($failures[$key]);
        session()->set(self::SESSION_LOGIN_FAILURES, $failures);
    }

    private function isLoginRateLimited(string $email): bool
    {
        return $this->loginFailures($email) >= $this->loginAttemptLimit();
    }

    private function loginFailureKey(string $email): string
    {
        return strtolower(trim($email));
    }

    private function matchesConfiguredEmail(string $email): bool
    {
        return $email !== '' && strcasecmp($email, $this->configuredEmail()) === 0;
    }

    private function matchesConfiguredPassword(string $password): bool
    {
        return hash_equals($this->configuredPassword(), $password);
    }

    private function findPersistedUserByEmail(string $email): ?User
    {
        if (!app()->has(\Marwa\DB\Connection\ConnectionManager::class)) {
            return null;
        }

        try {
            $user = User::findBy('email', $email);
        } catch (\Throwable) {
            return null;
        }

        if (!$user instanceof User) {
            return null;
        }

        if (!(bool) $user->getAttribute('is_active')) {
            return null;
        }

        return $user;
    }

    private function adminRoleId(): ?int
    {
        if (!app()->has(\Marwa\DB\Connection\ConnectionManager::class)) {
            return null;
        }

        try {
            $role = \App\Modules\Auth\Models\Role::findBySlug('admin');
        } catch (\Throwable) {
            return null;
        }

        return $role instanceof \App\Modules\Auth\Models\Role ? (int) $role->getKey() : null;
    }

    private function frameworkAuth(): ?FrameworkAuthManager
    {
        return app()->has(FrameworkAuthManager::class) ? app(FrameworkAuthManager::class) : null;
    }

    private function syncFrameworkUser(?User $user): void
    {
        $auth = $this->frameworkAuth();

        if ($auth === null) {
            return;
        }

        if ($user === null) {
            $auth->logout();

            return;
        }

        $auth->login($user);
    }

    private function syncFrameworkLogout(): void
    {
        $auth = $this->frameworkAuth();

        if ($auth === null) {
            return;
        }

        $auth->logout();
    }
}
